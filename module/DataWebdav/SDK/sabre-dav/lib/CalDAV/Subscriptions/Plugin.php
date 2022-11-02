<?php

namespace Sabre\CalDAV\Subscriptions;

use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;


class Plugin extends ServerPlugin {

    
    function initialize(Server $server) {

        $server->resourceTypeMapping['Sabre\\CalDAV\\Subscriptions\\ISubscription'] =
            '{http://calendarserver.org/ns/}subscribed';

        $server->xml->elementMap['{http://calendarserver.org/ns/}source'] =
            'Sabre\\DAV\\Xml\\Property\\Href';

        $server->on('propFind', [$this, 'propFind'], 150);

    }

    
    function getFeatures() {

        return ['calendarserver-subscribed'];

    }

    
    function propFind(PropFind $propFind, INode $node) {

                        $props = [
            '{http://calendarserver.org/ns/}subscribed-strip-alarms',
            '{http://calendarserver.org/ns/}subscribed-strip-attachments',
            '{http://calendarserver.org/ns/}subscribed-strip-todos',
        ];

        foreach ($props as $prop) {

            if ($propFind->getStatus($prop) === 200) {
                $propFind->set($prop, '', 200);
            }

        }

    }

    
    function getPluginName() {

        return 'subscriptions';

    }

    
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'This plugin allows users to store iCalendar subscriptions in their calendar-home.',
            'link'        => null,
        ];

    }
}
