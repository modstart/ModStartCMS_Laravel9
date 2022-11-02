<?php

namespace Sabre\DAV;

use Sabre\DAV\Exception\BadRequest;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\Xml\ParseException;


class CorePlugin extends ServerPlugin {

    
    protected $server;

    
    function initialize(Server $server) {

        $this->server = $server;
        $server->on('method:GET',       [$this, 'httpGet']);
        $server->on('method:OPTIONS',   [$this, 'httpOptions']);
        $server->on('method:HEAD',      [$this, 'httpHead']);
        $server->on('method:DELETE',    [$this, 'httpDelete']);
        $server->on('method:PROPFIND',  [$this, 'httpPropFind']);
        $server->on('method:PROPPATCH', [$this, 'httpPropPatch']);
        $server->on('method:PUT',       [$this, 'httpPut']);
        $server->on('method:MKCOL',     [$this, 'httpMkcol']);
        $server->on('method:MOVE',      [$this, 'httpMove']);
        $server->on('method:COPY',      [$this, 'httpCopy']);
        $server->on('method:REPORT',    [$this, 'httpReport']);

        $server->on('propPatch',        [$this, 'propPatchProtectedPropertyCheck'], 90);
        $server->on('propPatch',        [$this, 'propPatchNodeUpdate'], 200);
        $server->on('propFind',         [$this, 'propFind']);
        $server->on('propFind',         [$this, 'propFindNode'], 120);
        $server->on('propFind',         [$this, 'propFindLate'], 200);

        $server->on('exception',        [$this, 'exception']);

    }

    
    function getPluginName() {

        return 'core';

    }

    
    function httpGet(RequestInterface $request, ResponseInterface $response) {

        $path = $request->getPath();
        $node = $this->server->tree->getNodeForPath($path);

        if (!$node instanceof IFile) return;

        $body = $node->get();

                if (is_string($body)) {
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $body);
            rewind($stream);
            $body = $stream;
        }

        
        $httpHeaders = $this->server->getHTTPHeaders($path);

        
        if (!isset($httpHeaders['Content-Type'])) {
            $httpHeaders['Content-Type'] = 'application/octet-stream';
        }


        if (isset($httpHeaders['Content-Length'])) {

            $nodeSize = $httpHeaders['Content-Length'];

                        unset($httpHeaders['Content-Length']);

        } else {
            $nodeSize = null;
        }

        $response->addHeaders($httpHeaders);

        $range = $this->server->getHTTPRange();
        $ifRange = $request->getHeader('If-Range');
        $ignoreRangeHeader = false;

                        if ($nodeSize && $range && $ifRange) {

                                    try {
                $ifRangeDate = new \DateTime($ifRange);

                                                if (!isset($httpHeaders['Last-Modified'])) $ignoreRangeHeader = true;
                else {
                    $modified = new \DateTime($httpHeaders['Last-Modified']);
                    if ($modified > $ifRangeDate) $ignoreRangeHeader = true;
                }

            } catch (\Exception $e) {

                                if (!isset($httpHeaders['ETag'])) $ignoreRangeHeader = true;
                elseif ($httpHeaders['ETag'] !== $ifRange) $ignoreRangeHeader = true;
            }
        }

                if (!$ignoreRangeHeader && $nodeSize && $range) {

                        if (!is_null($range[0])) {

                $start = $range[0];
                $end = $range[1] ? $range[1] : $nodeSize - 1;
                if ($start >= $nodeSize)
                    throw new Exception\RequestedRangeNotSatisfiable('The start offset (' . $range[0] . ') exceeded the size of the entity (' . $nodeSize . ')');

                if ($end < $start) throw new Exception\RequestedRangeNotSatisfiable('The end offset (' . $range[1] . ') is lower than the start offset (' . $range[0] . ')');
                if ($end >= $nodeSize) $end = $nodeSize - 1;

            } else {

                $start = $nodeSize - $range[1];
                $end = $nodeSize - 1;

                if ($start < 0) $start = 0;

            }

                                                if (!stream_get_meta_data($body)['seekable'] || fseek($body, $start, SEEK_SET) === -1) {
                $consumeBlock = 8192;
                for ($consumed = 0; $start - $consumed > 0;){
                    if (feof($body)) throw new Exception\RequestedRangeNotSatisfiable('The start offset (' . $start . ') exceeded the size of the entity (' . $consumed . ')');
                    $consumed += strlen(fread($body, min($start - $consumed, $consumeBlock)));
                }
            }

            $response->setHeader('Content-Length', $end - $start + 1);
            $response->setHeader('Content-Range', 'bytes ' . $start . '-' . $end . '/' . $nodeSize);
            $response->setStatus(206);
            $response->setBody($body);

        } else {

            if ($nodeSize) $response->setHeader('Content-Length', $nodeSize);
            $response->setStatus(200);
            $response->setBody($body);

        }
                        return false;

    }

    
    function httpOptions(RequestInterface $request, ResponseInterface $response) {

        $methods = $this->server->getAllowedMethods($request->getPath());

        $response->setHeader('Allow', strtoupper(implode(', ', $methods)));
        $features = ['1', '3', 'extended-mkcol'];

        foreach ($this->server->getPlugins() as $plugin) {
            $features = array_merge($features, $plugin->getFeatures());
        }

        $response->setHeader('DAV', implode(', ', $features));
        $response->setHeader('MS-Author-Via', 'DAV');
        $response->setHeader('Accept-Ranges', 'bytes');
        $response->setHeader('Content-Length', '0');
        $response->setStatus(200);

                        return false;

    }

    
    function httpHead(RequestInterface $request, ResponseInterface $response) {

                        $subRequest = clone $request;
        $subRequest->setMethod('GET');

        try {
            $this->server->invokeMethod($subRequest, $response, false);
            $response->setBody('');
        } catch (Exception\NotImplemented $e) {
                                                                        $response->setStatus(200);
            $response->setBody('');
            $response->setHeader('Content-Type', 'text/plain');
            $response->setHeader('X-Sabre-Real-Status', $e->getHTTPCode());
        }

                        return false;

    }

    
    function httpDelete(RequestInterface $request, ResponseInterface $response) {

        $path = $request->getPath();

        if (!$this->server->emit('beforeUnbind', [$path])) return false;
        $this->server->tree->delete($path);
        $this->server->emit('afterUnbind', [$path]);

        $response->setStatus(204);
        $response->setHeader('Content-Length', '0');

                        return false;

    }

    
    function httpPropFind(RequestInterface $request, ResponseInterface $response) {

        $path = $request->getPath();

        $requestBody = $request->getBodyAsString();
        if (strlen($requestBody)) {
            try {
                $propFindXml = $this->server->xml->expect('{DAV:}propfind', $requestBody);
            } catch (ParseException $e) {
                throw new BadRequest($e->getMessage(), null, $e);
            }
        } else {
            $propFindXml = new Xml\Request\PropFind();
            $propFindXml->allProp = true;
            $propFindXml->properties = [];
        }

        $depth = $this->server->getHTTPDepth(1);
                if (!$this->server->enablePropfindDepthInfinity && $depth != 0) $depth = 1;

        $newProperties = $this->server->getPropertiesIteratorForPath($path, $propFindXml->properties, $depth);

                $response->setStatus(207);
        $response->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $response->setHeader('Vary', 'Brief,Prefer');

                                $features = ['1', '3', 'extended-mkcol'];
        foreach ($this->server->getPlugins() as $plugin) {
            $features = array_merge($features, $plugin->getFeatures());
        }
        $response->setHeader('DAV', implode(', ', $features));

        $prefer = $this->server->getHTTPPrefer();
        $minimal = $prefer['return'] === 'minimal';

        $data = $this->server->generateMultiStatus($newProperties, $minimal);
        $response->setBody($data);

                        return false;

    }

    
    function httpPropPatch(RequestInterface $request, ResponseInterface $response) {

        $path = $request->getPath();

        try {
            $propPatch = $this->server->xml->expect('{DAV:}propertyupdate', $request->getBody());
        } catch (ParseException $e) {
            throw new BadRequest($e->getMessage(), null, $e);
        }
        $newProperties = $propPatch->properties;

        $result = $this->server->updateProperties($path, $newProperties);

        $prefer = $this->server->getHTTPPrefer();
        $response->setHeader('Vary', 'Brief,Prefer');

        if ($prefer['return'] === 'minimal') {

                                                $ok = true;
            foreach ($result as $prop => $code) {
                if ((int)$code > 299) {
                    $ok = false;
                }
            }

            if ($ok) {

                $response->setStatus(204);
                return false;

            }

        }

        $response->setStatus(207);
        $response->setHeader('Content-Type', 'application/xml; charset=utf-8');


                $multiStatus = [];
        foreach ($result as $propertyName => $code) {
            if (isset($multiStatus[$code])) {
                $multiStatus[$code][$propertyName] = null;
            } else {
                $multiStatus[$code] = [$propertyName => null];
            }
        }
        $multiStatus['href'] = $path;

        $response->setBody(
            $this->server->generateMultiStatus([$multiStatus])
        );

                        return false;

    }

    
    function httpPut(RequestInterface $request, ResponseInterface $response) {

        $body = $request->getBodyAsStream();
        $path = $request->getPath();

                if ($request->getHeader('Content-Range')) {
            
            throw new Exception\BadRequest('Content-Range on PUT requests are forbidden.');
        }

                if (($expected = $request->getHeader('X-Expected-Entity-Length')) && $expected > 0) {

            

                        $firstByte = fread($body, 1);
            if (strlen($firstByte) !== 1) {
                throw new Exception\Forbidden('This server is not compatible with OS/X finder. Consider using a different WebDAV client or webserver.');
            }

                        
            $newBody = fopen('php://temp', 'r+');
            fwrite($newBody, $firstByte);
            stream_copy_to_stream($body, $newBody);
            rewind($newBody);

            $body = $newBody;

        }

        if ($this->server->tree->nodeExists($path)) {

            $node = $this->server->tree->getNodeForPath($path);

                        if (!($node instanceof IFile)) throw new Exception\Conflict('PUT is not allowed on non-files.');

            if (!$this->server->updateFile($path, $body, $etag)) {
                return false;
            }

            $response->setHeader('Content-Length', '0');
            if ($etag) $response->setHeader('ETag', $etag);
            $response->setStatus(204);

        } else {

            $etag = null;
                        if (!$this->server->createFile($path, $body, $etag)) {
                                return false;
            }

            $response->setHeader('Content-Length', '0');
            if ($etag) $response->setHeader('ETag', $etag);
            $response->setStatus(201);

        }

                        return false;

    }


    
    function httpMkcol(RequestInterface $request, ResponseInterface $response) {

        $requestBody = $request->getBodyAsString();
        $path = $request->getPath();

        if ($requestBody) {

            $contentType = $request->getHeader('Content-Type');
            if (strpos($contentType, 'application/xml') !== 0 && strpos($contentType, 'text/xml') !== 0) {

                                throw new Exception\UnsupportedMediaType('The request body for the MKCOL request must have an xml Content-Type');

            }

            try {
                $mkcol = $this->server->xml->expect('{DAV:}mkcol', $requestBody);
            } catch (\Sabre\Xml\ParseException $e) {
                throw new Exception\BadRequest($e->getMessage(), null, $e);
            }

            $properties = $mkcol->getProperties();

            if (!isset($properties['{DAV:}resourcetype']))
                throw new Exception\BadRequest('The mkcol request must include a {DAV:}resourcetype property');

            $resourceType = $properties['{DAV:}resourcetype']->getValue();
            unset($properties['{DAV:}resourcetype']);

        } else {

            $properties = [];
            $resourceType = ['{DAV:}collection'];

        }

        $mkcol = new MkCol($resourceType, $properties);

        $result = $this->server->createCollection($path, $mkcol);

        if (is_array($result)) {
            $response->setStatus(207);
            $response->setHeader('Content-Type', 'application/xml; charset=utf-8');

            $response->setBody(
                $this->server->generateMultiStatus([$result])
            );

        } else {
            $response->setHeader('Content-Length', '0');
            $response->setStatus(201);
        }

                        return false;

    }

    
    function httpMove(RequestInterface $request, ResponseInterface $response) {

        $path = $request->getPath();

        $moveInfo = $this->server->getCopyAndMoveInfo($request);

        if ($moveInfo['destinationExists']) {

            if (!$this->server->emit('beforeUnbind', [$moveInfo['destination']])) return false;

        }
        if (!$this->server->emit('beforeUnbind', [$path])) return false;
        if (!$this->server->emit('beforeBind', [$moveInfo['destination']])) return false;
        if (!$this->server->emit('beforeMove', [$path, $moveInfo['destination']])) return false;

        if ($moveInfo['destinationExists']) {

            $this->server->tree->delete($moveInfo['destination']);
            $this->server->emit('afterUnbind', [$moveInfo['destination']]);

        }

        $this->server->tree->move($path, $moveInfo['destination']);

                                        $this->server->emit('afterMove', [$path, $moveInfo['destination']]);
        $this->server->emit('afterUnbind', [$path]);
        $this->server->emit('afterBind', [$moveInfo['destination']]);

                $response->setHeader('Content-Length', '0');
        $response->setStatus($moveInfo['destinationExists'] ? 204 : 201);

                        return false;

    }

    
    function httpCopy(RequestInterface $request, ResponseInterface $response) {

        $path = $request->getPath();

        $copyInfo = $this->server->getCopyAndMoveInfo($request);

        if (!$this->server->emit('beforeBind', [$copyInfo['destination']])) return false;
        if ($copyInfo['destinationExists']) {
            if (!$this->server->emit('beforeUnbind', [$copyInfo['destination']])) return false;
            $this->server->tree->delete($copyInfo['destination']);
        }

        $this->server->tree->copy($path, $copyInfo['destination']);
        $this->server->emit('afterBind', [$copyInfo['destination']]);

                $response->setHeader('Content-Length', '0');
        $response->setStatus($copyInfo['destinationExists'] ? 204 : 201);

                        return false;


    }

    
    function httpReport(RequestInterface $request, ResponseInterface $response) {

        $path = $request->getPath();

        $result = $this->server->xml->parse(
            $request->getBody(),
            $request->getUrl(),
            $rootElementName
        );

        if ($this->server->emit('report', [$rootElementName, $result, $path])) {

                        throw new Exception\ReportNotSupported();

        }

                        return false;

    }

    
    function propPatchProtectedPropertyCheck($path, PropPatch $propPatch) {

                $mutations = $propPatch->getMutations();

        $protected = array_intersect(
            $this->server->protectedProperties,
            array_keys($mutations)
        );

        if ($protected) {
            $propPatch->setResultCode($protected, 403);
        }

    }

    
    function propPatchNodeUpdate($path, PropPatch $propPatch) {

                $node = $this->server->tree->getNodeForPath($path);

        if ($node instanceof IProperties) {
            $node->propPatch($propPatch);
        }

    }

    
    function propFind(PropFind $propFind, INode $node) {

        $propFind->handle('{DAV:}getlastmodified', function() use ($node) {
            $lm = $node->getLastModified();
            if ($lm) {
                return new Xml\Property\GetLastModified($lm);
            }
        });

        if ($node instanceof IFile) {
            $propFind->handle('{DAV:}getcontentlength', [$node, 'getSize']);
            $propFind->handle('{DAV:}getetag', [$node, 'getETag']);
            $propFind->handle('{DAV:}getcontenttype', [$node, 'getContentType']);
        }

        if ($node instanceof IQuota) {
            $quotaInfo = null;
            $propFind->handle('{DAV:}quota-used-bytes', function() use (&$quotaInfo, $node) {
                $quotaInfo = $node->getQuotaInfo();
                return $quotaInfo[0];
            });
            $propFind->handle('{DAV:}quota-available-bytes', function() use (&$quotaInfo, $node) {
                if (!$quotaInfo) {
                    $quotaInfo = $node->getQuotaInfo();
                }
                return $quotaInfo[1];
            });
        }

        $propFind->handle('{DAV:}supported-report-set', function() use ($propFind) {
            $reports = [];
            foreach ($this->server->getPlugins() as $plugin) {
                $reports = array_merge($reports, $plugin->getSupportedReportSet($propFind->getPath()));
            }
            return new Xml\Property\SupportedReportSet($reports);
        });
        $propFind->handle('{DAV:}resourcetype', function() use ($node) {
            return new Xml\Property\ResourceType($this->server->getResourceTypeForNode($node));
        });
        $propFind->handle('{DAV:}supported-method-set', function() use ($propFind) {
            return new Xml\Property\SupportedMethodSet(
                $this->server->getAllowedMethods($propFind->getPath())
            );
        });

    }

    
    function propFindNode(PropFind $propFind, INode $node) {

        if ($node instanceof IProperties && $propertyNames = $propFind->get404Properties()) {

            $nodeProperties = $node->getProperties($propertyNames);
            foreach ($nodeProperties as $propertyName => $propertyValue) {
                $propFind->set($propertyName, $propertyValue, 200);
            }

        }

    }

    
    function propFindLate(PropFind $propFind, INode $node) {

        $propFind->handle('{http://calendarserver.org/ns/}getctag', function() use ($propFind) {

                                    $val = $propFind->get('{http://sabredav.org/ns}sync-token');
            if ($val) return $val;

            $val = $propFind->get('{DAV:}sync-token');
            if ($val && is_scalar($val)) {
                return $val;
            }
            if ($val && $val instanceof Xml\Property\Href) {
                return substr($val->getHref(), strlen(Sync\Plugin::SYNCTOKEN_PREFIX));
            }

                                    $result = $this->server->getProperties($propFind->getPath(), [
                '{http://sabredav.org/ns}sync-token',
                '{DAV:}sync-token',
            ]);

            if (isset($result['{http://sabredav.org/ns}sync-token'])) {
                return $result['{http://sabredav.org/ns}sync-token'];
            }
            if (isset($result['{DAV:}sync-token'])) {
                $val = $result['{DAV:}sync-token'];
                if (is_scalar($val)) {
                    return $val;
                } elseif ($val instanceof Xml\Property\Href) {
                    return substr($val->getHref(), strlen(Sync\Plugin::SYNCTOKEN_PREFIX));
                }
            }

        });

    }

    
    function exception($e) {

        $logLevel = \Psr\Log\LogLevel::CRITICAL;
        if ($e instanceof \Sabre\DAV\Exception) {
                                    $code = $e->getHTTPCode();

            if ($code >= 400 && $code < 500) {
                                $logLevel = \Psr\Log\LogLevel::INFO;
            } else {
                                                $logLevel = \Psr\Log\LogLevel::ERROR;
            }
        }

        $this->server->getLogger()->log(
            $logLevel,
            'Uncaught exception',
            [
                'exception' => $e,
            ]
        );
    }

    
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'The Core plugin provides a lot of the basic functionality required by WebDAV, such as a default implementation for all HTTP and WebDAV methods.',
            'link'        => null,
        ];

    }
}
