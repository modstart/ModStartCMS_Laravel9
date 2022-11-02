<?php

namespace Sabre\DAV\Locks;

use Sabre\DAV;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;


class Plugin extends DAV\ServerPlugin {

    
    protected $locksBackend;

    
    protected $server;

    
    function __construct(Backend\BackendInterface $locksBackend) {

        $this->locksBackend = $locksBackend;

    }

    
    function initialize(DAV\Server $server) {

        $this->server = $server;

        $this->server->xml->elementMap['{DAV:}lockinfo'] = 'Sabre\\DAV\\Xml\\Request\\Lock';

        $server->on('method:LOCK',    [$this, 'httpLock']);
        $server->on('method:UNLOCK',  [$this, 'httpUnlock']);
        $server->on('validateTokens', [$this, 'validateTokens']);
        $server->on('propFind',       [$this, 'propFind']);
        $server->on('afterUnbind',    [$this, 'afterUnbind']);

    }

    
    function getPluginName() {

        return 'locks';

    }

    
    function propFind(DAV\PropFind $propFind, DAV\INode $node) {

        $propFind->handle('{DAV:}supportedlock', function() {
            return new DAV\Xml\Property\SupportedLock();
        });
        $propFind->handle('{DAV:}lockdiscovery', function() use ($propFind) {
            return new DAV\Xml\Property\LockDiscovery(
                $this->getLocks($propFind->getPath())
            );
        });

    }

    
    function getHTTPMethods($uri) {

        return ['LOCK','UNLOCK'];

    }

    
    function getFeatures() {

        return [2];

    }

    
    function getLocks($uri, $returnChildLocks = false) {

        return $this->locksBackend->getLocks($uri, $returnChildLocks);

    }

    
    function httpLock(RequestInterface $request, ResponseInterface $response) {

        $uri = $request->getPath();

        $existingLocks = $this->getLocks($uri);

        if ($body = $request->getBodyAsString()) {
            
            $existingLock = null;
                        foreach ($existingLocks as $existingLock) {
                if ($existingLock->scope === LockInfo::EXCLUSIVE) {
                    throw new DAV\Exception\ConflictingLock($existingLock);
                }
            }

            $lockInfo = $this->parseLockRequest($body);
            $lockInfo->depth = $this->server->getHTTPDepth();
            $lockInfo->uri = $uri;
            if ($existingLock && $lockInfo->scope != LockInfo::SHARED)
                throw new DAV\Exception\ConflictingLock($existingLock);

        } else {

                        $existingLocks = $this->getLocks($uri);
            $conditions = $this->server->getIfConditions($request);
            $found = null;

            foreach ($existingLocks as $existingLock) {
                foreach ($conditions as $condition) {
                    foreach ($condition['tokens'] as $token) {
                        if ($token['token'] === 'opaquelocktoken:' . $existingLock->token) {
                            $found = $existingLock;
                            break 3;
                        }
                    }
                }
            }

                        if (is_null($found)) {
                if ($existingLocks) {
                    throw new DAV\Exception\Locked(reset($existingLocks));
                } else {
                    throw new DAV\Exception\BadRequest('An xml body is required for lock requests');
                }

            }

                        $lockInfo = $found;

                        if ($uri != $lockInfo->uri) $uri = $lockInfo->uri;

        }

        if ($timeout = $this->getTimeoutHeader()) $lockInfo->timeout = $timeout;

        $newFile = false;

                try {
            $this->server->tree->getNodeForPath($uri);

                                                            
        } catch (DAV\Exception\NotFound $e) {

                        $this->server->createFile($uri, fopen('php://memory', 'r'));
            $newFile = true;

        }

        $this->lockNode($uri, $lockInfo);

        $response->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $response->setHeader('Lock-Token', '<opaquelocktoken:' . $lockInfo->token . '>');
        $response->setStatus($newFile ? 201 : 200);
        $response->setBody($this->generateLockResponse($lockInfo));

                        return false;

    }

    
    function httpUnlock(RequestInterface $request, ResponseInterface $response) {

        $lockToken = $request->getHeader('Lock-Token');

                if (!$lockToken) throw new DAV\Exception\BadRequest('No lock token was supplied');

        $path = $request->getPath();
        $locks = $this->getLocks($path);

                        if ($lockToken[0] !== '<') $lockToken = '<' . $lockToken . '>';

        foreach ($locks as $lock) {

            if ('<opaquelocktoken:' . $lock->token . '>' == $lockToken) {

                $this->unlockNode($path, $lock);
                $response->setHeader('Content-Length', '0');
                $response->setStatus(204);

                                                return false;

            }

        }

                throw new DAV\Exception\LockTokenMatchesRequestUri();

    }

    
    function afterUnbind($path) {

        $locks = $this->getLocks($path, $includeChildren = true);
        foreach ($locks as $lock) {
            $this->unlockNode($path, $lock);
        }

    }

    
    function lockNode($uri, LockInfo $lockInfo) {

        if (!$this->server->emit('beforeLock', [$uri, $lockInfo])) return;
        return $this->locksBackend->lock($uri, $lockInfo);

    }

    
    function unlockNode($uri, LockInfo $lockInfo) {

        if (!$this->server->emit('beforeUnlock', [$uri, $lockInfo])) return;
        return $this->locksBackend->unlock($uri, $lockInfo);

    }


    
    function getTimeoutHeader() {

        $header = $this->server->httpRequest->getHeader('Timeout');

        if ($header) {

            if (stripos($header, 'second-') === 0) $header = (int)(substr($header, 7));
            elseif (stripos($header, 'infinite') === 0) $header = LockInfo::TIMEOUT_INFINITE;
            else throw new DAV\Exception\BadRequest('Invalid HTTP timeout header');

        } else {

            $header = 0;

        }

        return $header;

    }

    
    protected function generateLockResponse(LockInfo $lockInfo) {

        return $this->server->xml->write('{DAV:}prop', [
            '{DAV:}lockdiscovery' =>
                new DAV\Xml\Property\LockDiscovery([$lockInfo])
        ]);
    }

    
    function validateTokens(RequestInterface $request, &$conditions) {

                $mustLocks = [];
        $method = $request->getMethod();

                        switch ($method) {

            case 'DELETE' :
                $mustLocks = array_merge($mustLocks, $this->getLocks(
                    $request->getPath(),
                    true
                ));
                break;
            case 'MKCOL' :
            case 'MKCALENDAR' :
            case 'PROPPATCH' :
            case 'PUT' :
            case 'PATCH' :
                $mustLocks = array_merge($mustLocks, $this->getLocks(
                    $request->getPath(),
                    false
                ));
                break;
            case 'MOVE' :
                $mustLocks = array_merge($mustLocks, $this->getLocks(
                    $request->getPath(),
                    true
                ));
                $mustLocks = array_merge($mustLocks, $this->getLocks(
                    $this->server->calculateUri($request->getHeader('Destination')),
                    false
                ));
                break;
            case 'COPY' :
                $mustLocks = array_merge($mustLocks, $this->getLocks(
                    $this->server->calculateUri($request->getHeader('Destination')),
                    false
                ));
                break;
            case 'LOCK' :
                                                foreach ($conditions as $ii => $condition) {
                    foreach ($condition['tokens'] as $jj => $token) {
                        $conditions[$ii]['tokens'][$jj]['validToken'] = true;
                    }
                }
                return;

        }

                        $tmp = [];
        foreach ($mustLocks as $lock) $tmp[$lock->token] = $lock;
        $mustLocks = array_values($tmp);

        foreach ($conditions as $kk => $condition) {

            foreach ($condition['tokens'] as $ii => $token) {

                                if (substr($token['token'], 0, 16) !== 'opaquelocktoken:') {
                    continue;
                }

                $checkToken = substr($token['token'], 16);
                                foreach ($mustLocks as $jj => $mustLock) {

                    if ($mustLock->token == $checkToken) {

                                                                        unset($mustLocks[$jj]);

                                                $conditions[$kk]['tokens'][$ii]['validToken'] = true;

                                                continue 2;

                    }

                }

                                                                                                                                                                                $oddLocks = $this->getLocks($condition['uri']);
                foreach ($oddLocks as $oddLock) {

                    if ($oddLock->token === $checkToken) {

                                                $conditions[$kk]['tokens'][$ii]['validToken'] = true;
                        continue 2;

                    }
                }

                                

            }

        }

                        if ($mustLocks) {

            throw new DAV\Exception\Locked(reset($mustLocks));

        }

    }

    
    protected function parseLockRequest($body) {

        $result = $this->server->xml->expect(
            '{DAV:}lockinfo',
            $body
        );

        $lockInfo = new LockInfo();

        $lockInfo->owner = $result->owner;
        $lockInfo->token = DAV\UUIDUtil::getUUID();
        $lockInfo->scope = $result->scope;

        return $lockInfo;

    }

    
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'The locks plugin turns this server into a class-2 WebDAV server and adds support for LOCK and UNLOCK',
            'link'        => 'http://sabre.io/dav/locks/',
        ];

    }

}
