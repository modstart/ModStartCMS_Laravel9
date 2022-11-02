<?php

namespace Sabre\DAV;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sabre\Event\EventEmitter;
use Sabre\HTTP;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\HTTP\URLUtil;
use Sabre\Uri;


class Server extends EventEmitter implements LoggerAwareInterface {

    use LoggerAwareTrait;

    
    const DEPTH_INFINITY = -1;

    
    const NS_SABREDAV = 'http://sabredav.org/ns';

    
    public $tree;

    
    protected $baseUri = null;

    
    public $httpResponse;

    
    public $httpRequest;

    
    public $sapi;

    
    protected $plugins = [];

    
    public $transactionType;

    
    public $protectedProperties = [

                '{DAV:}getcontentlength',
        '{DAV:}getetag',
        '{DAV:}getlastmodified',
        '{DAV:}lockdiscovery',
        '{DAV:}supportedlock',

                '{DAV:}quota-available-bytes',
        '{DAV:}quota-used-bytes',

                '{DAV:}supported-privilege-set',
        '{DAV:}current-user-privilege-set',
        '{DAV:}acl',
        '{DAV:}acl-restrictions',
        '{DAV:}inherited-acl-set',

                '{DAV:}supported-method-set',
        '{DAV:}supported-report-set',

                '{DAV:}sync-token',

                '{http://calendarserver.org/ns/}ctag',

                '{http://sabredav.org/ns}sync-token',

    ];

    
    public $debugExceptions = false;

    
    public $resourceTypeMapping = [
        'Sabre\\DAV\\ICollection' => '{DAV:}collection',
    ];

    
    public $enablePropfindDepthInfinity = false;

    
    public $xml;

    
    static $exposeVersion = true;

    
    function __construct($treeOrNode = null) {

        if ($treeOrNode instanceof Tree) {
            $this->tree = $treeOrNode;
        } elseif ($treeOrNode instanceof INode) {
            $this->tree = new Tree($treeOrNode);
        } elseif (is_array($treeOrNode)) {

                                    foreach ($treeOrNode as $node) {
                if (!($node instanceof INode)) {
                    throw new Exception('Invalid argument passed to constructor. If you\'re passing an array, all the values must implement Sabre\\DAV\\INode');
                }
            }

            $root = new SimpleCollection('root', $treeOrNode);
            $this->tree = new Tree($root);

        } elseif (is_null($treeOrNode)) {
            $root = new SimpleCollection('root');
            $this->tree = new Tree($root);
        } else {
            throw new Exception('Invalid argument passed to constructor. Argument must either be an instance of Sabre\\DAV\\Tree, Sabre\\DAV\\INode, an array or null');
        }

        $this->xml = new Xml\Service();
        $this->sapi = new HTTP\Sapi();
        $this->httpResponse = new HTTP\Response();
        $this->httpRequest = $this->sapi->getRequest();
        $this->addPlugin(new CorePlugin());

    }

    
    function exec() {

        try {

                                                                                    $this->httpResponse->setHTTPVersion($this->httpRequest->getHTTPVersion());

                        $this->httpRequest->setBaseUrl($this->getBaseUri());
            $this->invokeMethod($this->httpRequest, $this->httpResponse);

        } catch (\Exception $e) {

            try {
                $this->emit('exception', [$e]);
            } catch (\Exception $ignore) {
            }
            $DOM = new \DOMDocument('1.0', 'utf-8');
            $DOM->formatOutput = true;

            $error = $DOM->createElementNS('DAV:', 'd:error');
            $error->setAttribute('xmlns:s', self::NS_SABREDAV);
            $DOM->appendChild($error);

            $h = function($v) {

                return htmlspecialchars($v, ENT_NOQUOTES, 'UTF-8');

            };

            if (self::$exposeVersion) {
                $error->appendChild($DOM->createElement('s:sabredav-version', $h(Version::VERSION)));
            }

            $error->appendChild($DOM->createElement('s:exception', $h(get_class($e))));
            $error->appendChild($DOM->createElement('s:message', $h($e->getMessage())));
            if ($this->debugExceptions) {
                $error->appendChild($DOM->createElement('s:file', $h($e->getFile())));
                $error->appendChild($DOM->createElement('s:line', $h($e->getLine())));
                $error->appendChild($DOM->createElement('s:code', $h($e->getCode())));
                $error->appendChild($DOM->createElement('s:stacktrace', $h($e->getTraceAsString())));
            }

            if ($this->debugExceptions) {
                $previous = $e;
                while ($previous = $previous->getPrevious()) {
                    $xPrevious = $DOM->createElement('s:previous-exception');
                    $xPrevious->appendChild($DOM->createElement('s:exception', $h(get_class($previous))));
                    $xPrevious->appendChild($DOM->createElement('s:message', $h($previous->getMessage())));
                    $xPrevious->appendChild($DOM->createElement('s:file', $h($previous->getFile())));
                    $xPrevious->appendChild($DOM->createElement('s:line', $h($previous->getLine())));
                    $xPrevious->appendChild($DOM->createElement('s:code', $h($previous->getCode())));
                    $xPrevious->appendChild($DOM->createElement('s:stacktrace', $h($previous->getTraceAsString())));
                    $error->appendChild($xPrevious);
                }
            }


            if ($e instanceof Exception) {

                $httpCode = $e->getHTTPCode();
                $e->serialize($this, $error);
                $headers = $e->getHTTPHeaders($this);

            } else {

                $httpCode = 500;
                $headers = [];

            }
            $headers['Content-Type'] = 'application/xml; charset=utf-8';

            $this->httpResponse->setStatus($httpCode);
            $this->httpResponse->setHeaders($headers);
            $this->httpResponse->setBody($DOM->saveXML());
            $this->sapi->sendResponse($this->httpResponse);

        }

    }

    
    function setBaseUri($uri) {

                if ($uri[strlen($uri) - 1] !== '/')
            $uri .= '/';

        $this->baseUri = $uri;

    }

    
    function getBaseUri() {

        if (is_null($this->baseUri)) $this->baseUri = $this->guessBaseUri();
        return $this->baseUri;

    }

    
    function guessBaseUri() {

        $pathInfo = $this->httpRequest->getRawServerValue('PATH_INFO');
        $uri = $this->httpRequest->getRawServerValue('REQUEST_URI');

                if (!empty($pathInfo)) {

                        if ($pos = strpos($uri, '?'))
                $uri = substr($uri, 0, $pos);

                                                                        $decodedUri = URLUtil::decodePath($uri);

                        if (substr($decodedUri, strlen($decodedUri) - strlen($pathInfo)) === $pathInfo) {
                $baseUri = substr($decodedUri, 0, strlen($decodedUri) - strlen($pathInfo));
                return rtrim($baseUri, '/') . '/';
            }

            throw new Exception('The REQUEST_URI (' . $uri . ') did not end with the contents of PATH_INFO (' . $pathInfo . '). This server might be misconfigured.');

        }

                return '/';

    }

    
    function addPlugin(ServerPlugin $plugin) {

        $this->plugins[$plugin->getPluginName()] = $plugin;
        $plugin->initialize($this);

    }

    
    function getPlugin($name) {

        if (isset($this->plugins[$name]))
            return $this->plugins[$name];

        return null;

    }

    
    function getPlugins() {

        return $this->plugins;

    }

    
    function getLogger() {

        if (!$this->logger) {
            $this->logger = new NullLogger();
        }
        return $this->logger;

    }

    
    function invokeMethod(RequestInterface $request, ResponseInterface $response, $sendResponse = true) {

        $method = $request->getMethod();

        if (!$this->emit('beforeMethod:' . $method, [$request, $response])) return;
        if (!$this->emit('beforeMethod', [$request, $response])) return;

        if (self::$exposeVersion) {
            $response->setHeader('X-Sabre-Version', Version::VERSION);
        }

        $this->transactionType = strtolower($method);

        if (!$this->checkPreconditions($request, $response)) {
            $this->sapi->sendResponse($response);
            return;
        }

        if ($this->emit('method:' . $method, [$request, $response])) {
            if ($this->emit('method', [$request, $response])) {
                $exMessage = "There was no plugin in the system that was willing to handle this " . $method . " method.";
                if ($method === "GET") {
                    $exMessage .= " Enable the Browser plugin to get a better result here.";
                }

                                throw new Exception\NotImplemented($exMessage);
            }
        }

        if (!$this->emit('afterMethod:' . $method, [$request, $response])) return;
        if (!$this->emit('afterMethod', [$request, $response])) return;

        if ($response->getStatus() === null) {
            throw new Exception('No subsystem set a valid HTTP status code. Something must have interrupted the request without providing further detail.');
        }
        if ($sendResponse) {
            $this->sapi->sendResponse($response);
            $this->emit('afterResponse', [$request, $response]);
        }

    }

    
    
    function getAllowedMethods($path) {

        $methods = [
            'OPTIONS',
            'GET',
            'HEAD',
            'DELETE',
            'PROPFIND',
            'PUT',
            'PROPPATCH',
            'COPY',
            'MOVE',
            'REPORT'
        ];

                try {
            $this->tree->getNodeForPath($path);
        } catch (Exception\NotFound $e) {
            $methods[] = 'MKCOL';
        }

                foreach ($this->plugins as $plugin) $methods = array_merge($methods, $plugin->getHTTPMethods($path));
        array_unique($methods);

        return $methods;

    }

    
    function getRequestUri() {

        return $this->calculateUri($this->httpRequest->getUrl());

    }

    
    function calculateUri($uri) {

        if ($uri[0] != '/' && strpos($uri, '://')) {

            $uri = parse_url($uri, PHP_URL_PATH);

        }

        $uri = Uri\normalize(str_replace('//', '/', $uri));
        $baseUri = Uri\normalize($this->getBaseUri());

        if (strpos($uri, $baseUri) === 0) {

            return trim(URLUtil::decodePath(substr($uri, strlen($baseUri))), '/');

                        } elseif ($uri . '/' === $baseUri) {

            return '';

        } else {

            throw new Exception\Forbidden('Requested uri (' . $uri . ') is out of base uri (' . $this->getBaseUri() . ')');

        }

    }

    
    function getHTTPDepth($default = self::DEPTH_INFINITY) {

                $depth = $this->httpRequest->getHeader('Depth');

        if (is_null($depth)) return $default;

        if ($depth == 'infinity') return self::DEPTH_INFINITY;


                if (!ctype_digit($depth)) return $default;

        return (int)$depth;

    }

    
    function getHTTPRange() {

        $range = $this->httpRequest->getHeader('range');
        if (is_null($range)) return null;

        
        if (!preg_match('/^bytes=([0-9]*)-([0-9]*)$/i', $range, $matches)) return null;

        if ($matches[1] === '' && $matches[2] === '') return null;

        return [
            $matches[1] !== '' ? $matches[1] : null,
            $matches[2] !== '' ? $matches[2] : null,
        ];

    }

    
    function getHTTPPrefer() {

        $result = [
                        'respond-async' => false,
                        'return' => null,
                        'wait' => null,
                        'handling' => false,
        ];

        if ($prefer = $this->httpRequest->getHeader('Prefer')) {

            $result = array_merge(
                $result,
                HTTP\parsePrefer($prefer)
            );

        } elseif ($this->httpRequest->getHeader('Brief') == 't') {
            $result['return'] = 'minimal';
        }

        return $result;

    }


    
    function getCopyAndMoveInfo(RequestInterface $request) {

                if (!$request->getHeader('Destination')) throw new Exception\BadRequest('The destination header was not supplied');
        $destination = $this->calculateUri($request->getHeader('Destination'));
        $overwrite = $request->getHeader('Overwrite');
        if (!$overwrite) $overwrite = 'T';
        if (strtoupper($overwrite) == 'T') $overwrite = true;
        elseif (strtoupper($overwrite) == 'F') $overwrite = false;
                else throw new Exception\BadRequest('The HTTP Overwrite header should be either T or F');

        list($destinationDir) = URLUtil::splitPath($destination);

        try {
            $destinationParent = $this->tree->getNodeForPath($destinationDir);
            if (!($destinationParent instanceof ICollection)) throw new Exception\UnsupportedMediaType('The destination node is not a collection');
        } catch (Exception\NotFound $e) {

                        throw new Exception\Conflict('The destination node is not found');
        }

        try {

            $destinationNode = $this->tree->getNodeForPath($destination);

                                    if (!$overwrite) throw new Exception\PreconditionFailed('The destination node already exists, and the overwrite header is set to false', 'Overwrite');

        } catch (Exception\NotFound $e) {

                        $destinationNode = false;

        }

        $requestPath = $request->getPath();
        if ($destination === $requestPath) {
            throw new Exception\Forbidden('Source and destination uri are identical.');
        }
        if (substr($destination, 0, strlen($requestPath) + 1) === $requestPath . '/') {
            throw new Exception\Conflict('The destination may not be part of the same subtree as the source path.');
        }

                return [
            'destination'       => $destination,
            'destinationExists' => !!$destinationNode,
            'destinationNode'   => $destinationNode,
        ];

    }

    
    function getProperties($path, $propertyNames) {

        $result = $this->getPropertiesForPath($path, $propertyNames, 0);
        if (isset($result[0][200])) {
            return $result[0][200];
        } else {
            return [];
        }

    }

    
    function getPropertiesForChildren($path, $propertyNames) {

        $result = [];
        foreach ($this->getPropertiesForPath($path, $propertyNames, 1) as $k => $row) {

                        if ($k === 0) continue;

            $result[$row['href']] = $row[200];

        }
        return $result;

    }

    
    function getHTTPHeaders($path) {

        $propertyMap = [
            '{DAV:}getcontenttype'   => 'Content-Type',
            '{DAV:}getcontentlength' => 'Content-Length',
            '{DAV:}getlastmodified'  => 'Last-Modified',
            '{DAV:}getetag'          => 'ETag',
        ];

        $properties = $this->getProperties($path, array_keys($propertyMap));

        $headers = [];
        foreach ($propertyMap as $property => $header) {
            if (!isset($properties[$property])) continue;

            if (is_scalar($properties[$property])) {
                $headers[$header] = $properties[$property];

                        } elseif ($properties[$property] instanceof Xml\Property\GetLastModified) {
                $headers[$header] = HTTP\Util::toHTTPDate($properties[$property]->getTime());
            }

        }

        return $headers;

    }

    
    private function generatePathNodes(PropFind $propFind, array $yieldFirst = null) {
        if ($yieldFirst !== null) {
            yield $yieldFirst;
        }
        $newDepth = $propFind->getDepth();
        $path = $propFind->getPath();

        if ($newDepth !== self::DEPTH_INFINITY) {
            $newDepth--;
        }

        $propertyNames = $propFind->getRequestedProperties();
        $propFindType = !empty($propertyNames) ? PropFind::NORMAL : PropFind::ALLPROPS;

        foreach ($this->tree->getChildren($path) as $childNode) {
            if ($path !== '') {
                $subPath = $path . '/' . $childNode->getName();
            } else {
                $subPath = $childNode->getName();
            }
            $subPropFind = new PropFind($subPath, $propertyNames, $newDepth, $propFindType);

            yield [
                $subPropFind,
                $childNode
            ];

            if (($newDepth === self::DEPTH_INFINITY || $newDepth >= 1) && $childNode instanceof ICollection) {
                foreach ($this->generatePathNodes($subPropFind) as $subItem) {
                    yield $subItem;
                }
            }

        }
    }

    
    function getPropertiesForPath($path, $propertyNames = [], $depth = 0) {

        return iterator_to_array($this->getPropertiesIteratorForPath($path, $propertyNames, $depth));

    }
    
    function getPropertiesIteratorForPath($path, $propertyNames = [], $depth = 0) {

                if (!$this->enablePropfindDepthInfinity && $depth != 0) $depth = 1;

        $path = trim($path, '/');

        $propFindType = $propertyNames ? PropFind::NORMAL : PropFind::ALLPROPS;
        $propFind = new PropFind($path, (array)$propertyNames, $depth, $propFindType);

        $parentNode = $this->tree->getNodeForPath($path);

        $propFindRequests = [[
            $propFind,
            $parentNode
        ]];

        if (($depth > 0 || $depth === self::DEPTH_INFINITY) && $parentNode instanceof ICollection) {
            $propFindRequests = $this->generatePathNodes(clone $propFind, current($propFindRequests));
        }

        foreach ($propFindRequests as $propFindRequest) {

            list($propFind, $node) = $propFindRequest;
            $r = $this->getPropertiesByNode($propFind, $node);
            if ($r) {
                $result = $propFind->getResultForMultiStatus();
                $result['href'] = $propFind->getPath();

                                                                                $resourceType = $this->getResourceTypeForNode($node);
                if (in_array('{DAV:}collection', $resourceType) || in_array('{DAV:}principal', $resourceType)) {
                    $result['href'] .= '/';
                }
                yield $result;
            }

        }

    }

    
    function getPropertiesForMultiplePaths(array $paths, array $propertyNames = []) {

        $result = [
        ];

        $nodes = $this->tree->getMultipleNodes($paths);

        foreach ($nodes as $path => $node) {

            $propFind = new PropFind($path, $propertyNames);
            $r = $this->getPropertiesByNode($propFind, $node);
            if ($r) {
                $result[$path] = $propFind->getResultForMultiStatus();
                $result[$path]['href'] = $path;

                $resourceType = $this->getResourceTypeForNode($node);
                if (in_array('{DAV:}collection', $resourceType) || in_array('{DAV:}principal', $resourceType)) {
                    $result[$path]['href'] .= '/';
                }
            }

        }

        return $result;

    }


    
    function getPropertiesByNode(PropFind $propFind, INode $node) {

        return $this->emit('propFind', [$propFind, $node]);

    }

    
    function createFile($uri, $data, &$etag = null) {

        list($dir, $name) = URLUtil::splitPath($uri);

        if (!$this->emit('beforeBind', [$uri])) return false;

        $parent = $this->tree->getNodeForPath($dir);
        if (!$parent instanceof ICollection) {
            throw new Exception\Conflict('Files can only be created as children of collections');
        }

                                                $modified = false;
        if (!$this->emit('beforeCreateFile', [$uri, &$data, $parent, &$modified])) return false;

        $etag = $parent->createFile($name, $data);

        if ($modified) $etag = null;

        $this->tree->markDirty($dir . '/' . $name);

        $this->emit('afterBind', [$uri]);
        $this->emit('afterCreateFile', [$uri, $parent]);

        return true;
    }

    
    function updateFile($uri, $data, &$etag = null) {

        $node = $this->tree->getNodeForPath($uri);

                                                $modified = false;
        if (!$this->emit('beforeWriteContent', [$uri, $node, &$data, &$modified])) return false;

        $etag = $node->put($data);
        if ($modified) $etag = null;
        $this->emit('afterWriteContent', [$uri, $node]);

        return true;
    }



    
    function createDirectory($uri) {

        $this->createCollection($uri, new MkCol(['{DAV:}collection'], []));

    }

    
    function createCollection($uri, MkCol $mkCol) {

        list($parentUri, $newName) = URLUtil::splitPath($uri);

                try {
            $parent = $this->tree->getNodeForPath($parentUri);

        } catch (Exception\NotFound $e) {
            throw new Exception\Conflict('Parent node does not exist');

        }

                if (!$parent instanceof ICollection) {
            throw new Exception\Conflict('Parent node is not a collection');
        }

                try {
            $parent->getChild($newName);

                        throw new Exception\MethodNotAllowed('The resource you tried to create already exists');

        } catch (Exception\NotFound $e) {
                    }


        if (!$this->emit('beforeBind', [$uri])) return;

        if ($parent instanceof IExtendedCollection) {

            
            $parent->createExtendedCollection($newName, $mkCol);

        } else {

            
            if (count($mkCol->getResourceType()) > 1) {
                throw new Exception\InvalidResourceType('The {DAV:}resourcetype you specified is not supported here.');
            }

            $parent->createDirectory($newName);

        }

                                if ($mkCol->getRemainingMutations()) {
            $this->emit('propPatch', [$uri, $mkCol]);
        }
        $success = $mkCol->commit();

        if (!$success) {
            $result = $mkCol->getResult();

            $formattedResult = [
                'href' => $uri,
            ];

            foreach ($result as $propertyName => $status) {

                if (!isset($formattedResult[$status])) {
                    $formattedResult[$status] = [];
                }
                $formattedResult[$status][$propertyName] = null;

            }
            return $formattedResult;
        }

        $this->tree->markDirty($parentUri);
        $this->emit('afterBind', [$uri]);

    }

    
    function updateProperties($path, array $properties) {

        $propPatch = new PropPatch($properties);
        $this->emit('propPatch', [$path, $propPatch]);
        $propPatch->commit();

        return $propPatch->getResult();

    }

    
    function checkPreconditions(RequestInterface $request, ResponseInterface $response) {

        $path = $request->getPath();
        $node = null;
        $lastMod = null;
        $etag = null;

        if ($ifMatch = $request->getHeader('If-Match')) {

                                                            try {
                $node = $this->tree->getNodeForPath($path);
            } catch (Exception\NotFound $e) {
                throw new Exception\PreconditionFailed('An If-Match header was specified and the resource did not exist', 'If-Match');
            }

                        if ($ifMatch !== '*') {

                                $ifMatch = explode(',', $ifMatch);
                $haveMatch = false;
                foreach ($ifMatch as $ifMatchItem) {

                                        $ifMatchItem = trim($ifMatchItem, ' ');

                    $etag = $node instanceof IFile ? $node->getETag() : null;
                    if ($etag === $ifMatchItem) {
                        $haveMatch = true;
                    } else {
                                                                        if (str_replace('\\"', '"', $ifMatchItem) === $etag) {
                            $haveMatch = true;
                        }
                    }

                }
                if (!$haveMatch) {
                    if ($etag) $response->setHeader('ETag', $etag);
                     throw new Exception\PreconditionFailed('An If-Match header was specified, but none of the specified the ETags matched.', 'If-Match');
                }
            }
        }

        if ($ifNoneMatch = $request->getHeader('If-None-Match')) {

                                                            $nodeExists = true;
            if (!$node) {
                try {
                    $node = $this->tree->getNodeForPath($path);
                } catch (Exception\NotFound $e) {
                    $nodeExists = false;
                }
            }
            if ($nodeExists) {
                $haveMatch = false;
                if ($ifNoneMatch === '*') $haveMatch = true;
                else {

                                        $ifNoneMatch = explode(',', $ifNoneMatch);
                    $etag = $node instanceof IFile ? $node->getETag() : null;

                    foreach ($ifNoneMatch as $ifNoneMatchItem) {

                                                $ifNoneMatchItem = trim($ifNoneMatchItem, ' ');

                        if ($etag === $ifNoneMatchItem) $haveMatch = true;

                    }

                }

                if ($haveMatch) {
                    if ($etag) $response->setHeader('ETag', $etag);
                    if ($request->getMethod() === 'GET') {
                        $response->setStatus(304);
                        return false;
                    } else {
                        throw new Exception\PreconditionFailed('An If-None-Match header was specified, but the ETag matched (or * was specified).', 'If-None-Match');
                    }
                }
            }

        }

        if (!$ifNoneMatch && ($ifModifiedSince = $request->getHeader('If-Modified-Since'))) {

                                                                                    $date = HTTP\Util::parseHTTPDate($ifModifiedSince);

            if ($date) {
                if (is_null($node)) {
                    $node = $this->tree->getNodeForPath($path);
                }
                $lastMod = $node->getLastModified();
                if ($lastMod) {
                    $lastMod = new \DateTime('@' . $lastMod);
                    if ($lastMod <= $date) {
                        $response->setStatus(304);
                        $response->setHeader('Last-Modified', HTTP\Util::toHTTPDate($lastMod));
                        return false;
                    }
                }
            }
        }

        if ($ifUnmodifiedSince = $request->getHeader('If-Unmodified-Since')) {

                                    $date = HTTP\Util::parseHTTPDate($ifUnmodifiedSince);

                        if ($date) {
                if (is_null($node)) {
                    $node = $this->tree->getNodeForPath($path);
                }
                $lastMod = $node->getLastModified();
                if ($lastMod) {
                    $lastMod = new \DateTime('@' . $lastMod);
                    if ($lastMod > $date) {
                        throw new Exception\PreconditionFailed('An If-Unmodified-Since header was specified, but the entity has been changed since the specified date.', 'If-Unmodified-Since');
                    }
                }
            }

        }

                                                                        $ifConditions = $this->getIfConditions($request);

        foreach ($ifConditions as $kk => $ifCondition) {
            foreach ($ifCondition['tokens'] as $ii => $token) {
                $ifConditions[$kk]['tokens'][$ii]['validToken'] = false;
            }
        }

                                $this->emit('validateTokens', [$request, &$ifConditions]);

        
                        foreach ($ifConditions as $ifCondition) {

            $uri = $ifCondition['uri'];
            $tokens = $ifCondition['tokens'];

                        foreach ($tokens as $token) {

                $tokenValid = $token['validToken'] || !$token['token'];

                $etagValid = false;
                if (!$token['etag']) {
                    $etagValid = true;
                }
                                                if ($token['etag'] && $tokenValid) {

                                                            $node = $this->tree->getNodeForPath($uri);
                    $etagValid = $node instanceof IFile && $node->getETag() == $token['etag'];

                }


                if (($tokenValid && $etagValid) ^ $token['negate']) {
                                        continue 2;
                }


            }

                                    throw new Exception\PreconditionFailed('Failed to find a valid token/etag combination for ' . $uri, 'If');

        }

        return true;

    }

    
    function getIfConditions(RequestInterface $request) {

        $header = $request->getHeader('If');
        if (!$header) return [];

        $matches = [];

        $regex = '/(?:\<(?P<uri>.*?)\>\s)?\((?P<not>Not\s)?(?:\<(?P<token>[^\>]*)\>)?(?:\s?)(?:\[(?P<etag>[^\]]*)\])?\)/im';
        preg_match_all($regex, $header, $matches, PREG_SET_ORDER);

        $conditions = [];

        foreach ($matches as $match) {

                                                if (!$match['uri'] && count($conditions)) {
                $conditions[count($conditions) - 1]['tokens'][] = [
                    'negate' => $match['not'] ? true : false,
                    'token'  => $match['token'],
                    'etag'   => isset($match['etag']) ? $match['etag'] : ''
                ];
            } else {

                if (!$match['uri']) {
                    $realUri = $request->getPath();
                } else {
                    $realUri = $this->calculateUri($match['uri']);
                }

                $conditions[] = [
                    'uri'    => $realUri,
                    'tokens' => [
                        [
                            'negate' => $match['not'] ? true : false,
                            'token'  => $match['token'],
                            'etag'   => isset($match['etag']) ? $match['etag'] : ''
                        ]
                    ],

                ];
            }

        }

        return $conditions;

    }

    
    function getResourceTypeForNode(INode $node) {

        $result = [];
        foreach ($this->resourceTypeMapping as $className => $resourceType) {
            if ($node instanceof $className) $result[] = $resourceType;
        }
        return $result;

    }

        

    
    function generateMultiStatus($fileProperties, $strip404s = false) {

        $w = $this->xml->getWriter();
        $w->openMemory();
        $w->contextUri = $this->baseUri;
        $w->startDocument();

        $w->startElement('{DAV:}multistatus');

        foreach ($fileProperties as $entry) {

            $href = $entry['href'];
            unset($entry['href']);
            if ($strip404s) {
                unset($entry[404]);
            }
            $response = new Xml\Element\Response(
                ltrim($href, '/'),
                $entry
            );
            $w->write([
                'name'  => '{DAV:}response',
                'value' => $response
            ]);
        }
        $w->endElement();

        return $w->outputMemory();

    }

}
