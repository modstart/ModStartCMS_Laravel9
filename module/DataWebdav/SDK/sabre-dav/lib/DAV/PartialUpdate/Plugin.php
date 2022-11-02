<?php

namespace Sabre\DAV\PartialUpdate;

use Sabre\DAV;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;


class Plugin extends DAV\ServerPlugin {

    const RANGE_APPEND = 1;
    const RANGE_START = 2;
    const RANGE_END = 3;

    
    protected $server;

    
    function initialize(DAV\Server $server) {

        $this->server = $server;
        $server->on('method:PATCH', [$this, 'httpPatch']);

    }

    
    function getPluginName() {

        return 'partialupdate';

    }

    
    function getHTTPMethods($uri) {

        $tree = $this->server->tree;

        if ($tree->nodeExists($uri)) {
            $node = $tree->getNodeForPath($uri);
            if ($node instanceof IPatchSupport) {
                return ['PATCH'];
            }
        }
        return [];

    }

    
    function getFeatures() {

        return ['sabredav-partialupdate'];

    }

    
    function httpPatch(RequestInterface $request, ResponseInterface $response) {

        $path = $request->getPath();

                $node = $this->server->tree->getNodeForPath($path);
        if (!$node instanceof IPatchSupport) {
            throw new DAV\Exception\MethodNotAllowed('The target resource does not support the PATCH method.');
        }

        $range = $this->getHTTPUpdateRange($request);

        if (!$range) {
            throw new DAV\Exception\BadRequest('No valid "X-Update-Range" found in the headers');
        }

        $contentType = strtolower(
            $request->getHeader('Content-Type')
        );

        if ($contentType != 'application/x-sabredav-partialupdate') {
            throw new DAV\Exception\UnsupportedMediaType('Unknown Content-Type header "' . $contentType . '"');
        }

        $len = $this->server->httpRequest->getHeader('Content-Length');
        if (!$len) throw new DAV\Exception\LengthRequired('A Content-Length header is required');

        switch ($range[0]) {
            case self::RANGE_START :
                                if (!$range[2]) {
                    $range[2] = $range[1] + $len - 1;
                } else {
                    if ($range[2] < $range[1]) {
                        throw new DAV\Exception\RequestedRangeNotSatisfiable('The end offset (' . $range[2] . ') is lower than the start offset (' . $range[1] . ')');
                    }
                    if ($range[2] - $range[1] + 1 != $len) {
                        throw new DAV\Exception\RequestedRangeNotSatisfiable('Actual data length (' . $len . ') is not consistent with begin (' . $range[1] . ') and end (' . $range[2] . ') offsets');
                    }
                }
                break;
        }

        if (!$this->server->emit('beforeWriteContent', [$path, $node, null]))
            return;

        $body = $this->server->httpRequest->getBody();


        $etag = $node->patch($body, $range[0], isset($range[1]) ? $range[1] : null);

        $this->server->emit('afterWriteContent', [$path, $node]);

        $response->setHeader('Content-Length', '0');
        if ($etag) $response->setHeader('ETag', $etag);
        $response->setStatus(204);

                return false;

    }

    
    function getHTTPUpdateRange(RequestInterface $request) {

        $range = $request->getHeader('X-Update-Range');
        if (is_null($range)) return null;

        
        if (!preg_match('/^(append)|(?:bytes=([0-9]+)-([0-9]*))|(?:bytes=(-[0-9]+))$/i', $range, $matches)) return null;

        if ($matches[1] === 'append') {
            return [self::RANGE_APPEND];
        } elseif (strlen($matches[2]) > 0) {
            return [self::RANGE_START, $matches[2], $matches[3] ?: null];
        } else {
            return [self::RANGE_END, $matches[4]];
        }

    }
}
