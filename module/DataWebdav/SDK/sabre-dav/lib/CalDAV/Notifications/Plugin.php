<?php

namespace Sabre\CalDAV\Notifications;

use Sabre\DAV;
use Sabre\DAV\INode as BaseINode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAVACL;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;


class Plugin extends ServerPlugin {

    
    const NS_CALENDARSERVER = 'http://calendarserver.org/ns/';

    
    protected $server;

    
    function getPluginName() {

        return 'notifications';

    }

    
    function initialize(Server $server) {

        $this->server = $server;
        $server->on('method:GET', [$this, 'httpGet'], 90);
        $server->on('propFind',   [$this, 'propFind']);

        $server->xml->namespaceMap[self::NS_CALENDARSERVER] = 'cs';
        $server->resourceTypeMapping['\\Sabre\\CalDAV\\Notifications\\ICollection'] = '{' . self::NS_CALENDARSERVER . '}notification';

        array_push($server->protectedProperties,
            '{' . self::NS_CALENDARSERVER . '}notification-URL',
            '{' . self::NS_CALENDARSERVER . '}notificationtype'
        );

    }

    
    function propFind(PropFind $propFind, BaseINode $node) {

        $caldavPlugin = $this->server->getPlugin('caldav');

        if ($node instanceof DAVACL\IPrincipal) {

            $principalUrl = $node->getPrincipalUrl();

                        $propFind->handle('{' . self::NS_CALENDARSERVER . '}notification-URL', function() use ($principalUrl, $caldavPlugin) {

                $notificationPath = $caldavPlugin->getCalendarHomeForPrincipal($principalUrl) . '/notifications/';
                return new DAV\Xml\Property\Href($notificationPath);

            });

        }

        if ($node instanceof INode) {

            $propFind->handle(
                '{' . self::NS_CALENDARSERVER . '}notificationtype',
                [$node, 'getNotificationType']
            );

        }

    }

    
    function httpGet(RequestInterface $request, ResponseInterface $response) {

        $path = $request->getPath();

        try {
            $node = $this->server->tree->getNodeForPath($path);
        } catch (DAV\Exception\NotFound $e) {
            return;
        }

        if (!$node instanceof INode)
            return;

        $writer = $this->server->xml->getWriter();
        $writer->contextUri = $this->server->getBaseUri();
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement('{http://calendarserver.org/ns/}notification');
        $node->getNotificationType()->xmlSerializeFull($writer);
        $writer->endElement();

        $response->setHeader('Content-Type', 'application/xml');
        $response->setHeader('ETag', $node->getETag());
        $response->setStatus(200);
        $response->setBody($writer->outputMemory());

                return false;

    }

    
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'Adds support for caldav-notifications, which is required to enable caldav-sharing.',
            'link'        => 'http://sabre.io/dav/caldav-sharing/',
        ];

    }

}
