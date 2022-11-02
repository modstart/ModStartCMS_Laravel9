<?php

namespace Sabre\DAV;

use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\HTTP\URLUtil;


class TemporaryFileFilterPlugin extends ServerPlugin {

    
    public $temporaryFilePatterns = [
        '/^\._(.*)$/',             '/^.DS_Store$/',           '/^desktop.ini$/',         '/^Thumbs.db$/',           '/^.(.*).swp$/',           '/^\.dat(.*)$/',           '/^~lock.(.*)#$/',     ];

    
    protected $server;

    
    private $dataDir;

    
    function __construct($dataDir = null) {

        if (!$dataDir) $dataDir = ini_get('session.save_path') . '/sabredav/';
        if (!is_dir($dataDir)) mkdir($dataDir);
        $this->dataDir = $dataDir;

    }

    
    function initialize(Server $server) {

        $this->server = $server;
        $server->on('beforeMethod',    [$this, 'beforeMethod']);
        $server->on('beforeCreateFile', [$this, 'beforeCreateFile']);

    }

    
    function beforeMethod(RequestInterface $request, ResponseInterface $response) {

        if (!$tempLocation = $this->isTempFile($request->getPath()))
            return;

        switch ($request->getMethod()) {
            case 'GET' :
                return $this->httpGet($request, $response, $tempLocation);
            case 'PUT' :
                return $this->httpPut($request, $response, $tempLocation);
            case 'PROPFIND' :
                return $this->httpPropfind($request, $response, $tempLocation);
            case 'DELETE' :
                return $this->httpDelete($request, $response, $tempLocation);
        }
        return;

    }

    
    function beforeCreateFile($uri, $data, ICollection $parent, $modified) {

        if ($tempPath = $this->isTempFile($uri)) {

            $hR = $this->server->httpResponse;
            $hR->setHeader('X-Sabre-Temp', 'true');
            file_put_contents($tempPath, $data);
            return false;
        }
        return;

    }

    
    protected function isTempFile($path) {

                list(, $tempPath) = URLUtil::splitPath($path);

        foreach ($this->temporaryFilePatterns as $tempFile) {

            if (preg_match($tempFile, $tempPath)) {
                return $this->getDataDir() . '/sabredav_' . md5($path) . '.tempfile';
            }

        }

        return false;

    }


    
    function httpGet(RequestInterface $request, ResponseInterface $hR, $tempLocation) {

        if (!file_exists($tempLocation)) return;

        $hR->setHeader('Content-Type', 'application/octet-stream');
        $hR->setHeader('Content-Length', filesize($tempLocation));
        $hR->setHeader('X-Sabre-Temp', 'true');
        $hR->setStatus(200);
        $hR->setBody(fopen($tempLocation, 'r'));
        return false;

    }

    
    function httpPut(RequestInterface $request, ResponseInterface $hR, $tempLocation) {

        $hR->setHeader('X-Sabre-Temp', 'true');

        $newFile = !file_exists($tempLocation);

        if (!$newFile && ($this->server->httpRequest->getHeader('If-None-Match'))) {
             throw new Exception\PreconditionFailed('The resource already exists, and an If-None-Match header was supplied');
        }

        file_put_contents($tempLocation, $this->server->httpRequest->getBody());
        $hR->setStatus($newFile ? 201 : 200);
        return false;

    }

    
    function httpDelete(RequestInterface $request, ResponseInterface $hR, $tempLocation) {

        if (!file_exists($tempLocation)) return;

        unlink($tempLocation);
        $hR->setHeader('X-Sabre-Temp', 'true');
        $hR->setStatus(204);
        return false;

    }

    
    function httpPropfind(RequestInterface $request, ResponseInterface $hR, $tempLocation) {

        if (!file_exists($tempLocation)) return;

        $hR->setHeader('X-Sabre-Temp', 'true');
        $hR->setStatus(207);
        $hR->setHeader('Content-Type', 'application/xml; charset=utf-8');

        $properties = [
            'href' => $request->getPath(),
            200    => [
                '{DAV:}getlastmodified'                 => new Xml\Property\GetLastModified(filemtime($tempLocation)),
                '{DAV:}getcontentlength'                => filesize($tempLocation),
                '{DAV:}resourcetype'                    => new Xml\Property\ResourceType(null),
                '{' . Server::NS_SABREDAV . '}tempFile' => true,

            ],
        ];

        $data = $this->server->generateMultiStatus([$properties]);
        $hR->setBody($data);
        return false;

    }


    
    protected function getDataDir()
    {
        return $this->dataDir;
    }
}
