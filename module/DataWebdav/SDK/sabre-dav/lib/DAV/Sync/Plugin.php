<?php

namespace Sabre\DAV\Sync;

use Sabre\DAV;
use Sabre\DAV\Xml\Request\SyncCollectionReport;
use Sabre\HTTP\RequestInterface;


class Plugin extends DAV\ServerPlugin {

    
    protected $server;

    const SYNCTOKEN_PREFIX = 'http://sabre.io/ns/sync/';

    
    function getPluginName() {

        return 'sync';

    }

    
    function initialize(DAV\Server $server) {

        $this->server = $server;
        $server->xml->elementMap['{DAV:}sync-collection'] = 'Sabre\\DAV\\Xml\\Request\\SyncCollectionReport';

        $self = $this;

        $server->on('report', function($reportName, $dom, $uri) use ($self) {

            if ($reportName === '{DAV:}sync-collection') {
                $this->server->transactionType = 'report-sync-collection';
                $self->syncCollection($uri, $dom);
                return false;
            }

        });

        $server->on('propFind',       [$this, 'propFind']);
        $server->on('validateTokens', [$this, 'validateTokens']);

    }

    
    function getSupportedReportSet($uri) {

        $node = $this->server->tree->getNodeForPath($uri);
        if ($node instanceof ISyncCollection && $node->getSyncToken()) {
            return [
                '{DAV:}sync-collection',
            ];
        }

        return [];

    }


    
    function syncCollection($uri, SyncCollectionReport $report) {

                $node = $this->server->tree->getNodeForPath($uri);
        if (!$node instanceof ISyncCollection) {
            throw new DAV\Exception\ReportNotSupported('The {DAV:}sync-collection REPORT is not supported on this url.');
        }
        $token = $node->getSyncToken();
        if (!$token) {
            throw new DAV\Exception\ReportNotSupported('No sync information is available at this node');
        }

        $syncToken = $report->syncToken;
        if (!is_null($syncToken)) {
                        if (substr($syncToken, 0, strlen(self::SYNCTOKEN_PREFIX)) !== self::SYNCTOKEN_PREFIX) {
                throw new DAV\Exception\InvalidSyncToken('Invalid or unknown sync token');
            }

            $syncToken = substr($syncToken, strlen(self::SYNCTOKEN_PREFIX));

        }
        $changeInfo = $node->getChanges($syncToken, $report->syncLevel, $report->limit);

        if (is_null($changeInfo)) {

            throw new DAV\Exception\InvalidSyncToken('Invalid or unknown sync token');

        }

                $this->sendSyncCollectionResponse(
            $changeInfo['syncToken'],
            $uri,
            $changeInfo['added'],
            $changeInfo['modified'],
            $changeInfo['deleted'],
            $report->properties
        );

    }

    
    protected function sendSyncCollectionResponse($syncToken, $collectionUrl, array $added, array $modified, array $deleted, array $properties) {


        $fullPaths = [];

                foreach (array_merge($added, $modified) as $item) {
            $fullPath = $collectionUrl . '/' . $item;
            $fullPaths[] = $fullPath;
        }

        $responses = [];
        foreach ($this->server->getPropertiesForMultiplePaths($fullPaths, $properties) as $fullPath => $props) {

                                    $responses[] = new DAV\Xml\Element\Response($fullPath, $props);

        }



                        foreach ($deleted as $item) {

            $fullPath = $collectionUrl . '/' . $item;
            $responses[] = new DAV\Xml\Element\Response($fullPath, [], 404);

        }
        $multiStatus = new DAV\Xml\Response\MultiStatus($responses, self::SYNCTOKEN_PREFIX . $syncToken);

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setBody(
            $this->server->xml->write('{DAV:}multistatus', $multiStatus, $this->server->getBaseUri())
        );

    }

    
    function propFind(DAV\PropFind $propFind, DAV\INode $node) {

        $propFind->handle('{DAV:}sync-token', function() use ($node) {
            if (!$node instanceof ISyncCollection || !$token = $node->getSyncToken()) {
                return;
            }
            return self::SYNCTOKEN_PREFIX . $token;
        });

    }

    
    function validateTokens(RequestInterface $request, &$conditions) {

        foreach ($conditions as $kk => $condition) {

            foreach ($condition['tokens'] as $ii => $token) {

                                if (substr($token['token'], 0, strlen(self::SYNCTOKEN_PREFIX)) !== self::SYNCTOKEN_PREFIX) {
                    continue;
                }

                                $node = $this->server->tree->getNodeForPath($condition['uri']);

                if (
                    $node instanceof ISyncCollection &&
                    $node->getSyncToken() == substr($token['token'], strlen(self::SYNCTOKEN_PREFIX))
                ) {
                    $conditions[$kk]['tokens'][$ii]['validToken'] = true;
                }

            }

        }

    }

    
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'Adds support for WebDAV Collection Sync (rfc6578)',
            'link'        => 'http://sabre.io/dav/sync/',
        ];

    }

}
