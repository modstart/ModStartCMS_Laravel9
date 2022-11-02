<?php

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\DAV\Xml\Property\LocalHref;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;


class SharingPlugin extends DAV\ServerPlugin {

    
    protected $server;

    
    function getFeatures() {

        return ['calendarserver-sharing'];

    }

    
    function getPluginName() {

        return 'caldav-sharing';

    }

    
    function initialize(DAV\Server $server) {

        $this->server = $server;

        if (is_null($this->server->getPlugin('sharing'))) {
            throw new \LogicException('The generic "sharing" plugin must be loaded before the caldav sharing plugin. Call $server->addPlugin(new \Sabre\DAV\Sharing\Plugin()); before this one.');
        }

        array_push(
            $this->server->protectedProperties,
            '{' . Plugin::NS_CALENDARSERVER . '}invite',
            '{' . Plugin::NS_CALENDARSERVER . '}allowed-sharing-modes',
            '{' . Plugin::NS_CALENDARSERVER . '}shared-url'
        );

        $this->server->xml->elementMap['{' . Plugin::NS_CALENDARSERVER . '}share'] = 'Sabre\\CalDAV\\Xml\\Request\\Share';
        $this->server->xml->elementMap['{' . Plugin::NS_CALENDARSERVER . '}invite-reply'] = 'Sabre\\CalDAV\\Xml\\Request\\InviteReply';

        $this->server->on('propFind',     [$this, 'propFindEarly']);
        $this->server->on('propFind',     [$this, 'propFindLate'], 150);
        $this->server->on('propPatch',    [$this, 'propPatch'], 40);
        $this->server->on('method:POST',  [$this, 'httpPost']);

    }

    
    function propFindEarly(DAV\PropFind $propFind, DAV\INode $node) {

        if ($node instanceof ISharedCalendar) {

            $propFind->handle('{' . Plugin::NS_CALENDARSERVER . '}invite', function() use ($node) {

                                $props = $this->server->getPropertiesForPath($node->getOwner(), [
                    '{http://sabredav.org/ns}email-address',
                    '{DAV:}displayname',
                ], 0);

                $ownerInfo = [
                    'href' => $node->getOwner(),
                ];

                if (isset($props[0][200])) {

                                                            if (isset($props[0][200]['{http://sabredav.org/ns}email-address'])) {
                        $ownerInfo['href'] = 'mailto:' . $props[0][200]['{http://sabredav.org/ns}email-address'];
                    }
                    if (isset($props[0][200]['{DAV:}displayname'])) {
                        $ownerInfo['commonName'] = $props[0][200]['{DAV:}displayname'];
                    }

                }

                return new Xml\Property\Invite(
                    $node->getInvites(),
                    $ownerInfo
                );

            });

        }

    }

    
    function propFindLate(DAV\PropFind $propFind, DAV\INode $node) {

        if ($node instanceof ISharedCalendar) {
            $shareAccess = $node->getShareAccess();
            if ($rt = $propFind->get('{DAV:}resourcetype')) {
                switch ($shareAccess) {
                    case \Sabre\DAV\Sharing\Plugin::ACCESS_SHAREDOWNER :
                        $rt->add('{' . Plugin::NS_CALENDARSERVER . '}shared-owner');
                        break;
                    case \Sabre\DAV\Sharing\Plugin::ACCESS_READ :
                    case \Sabre\DAV\Sharing\Plugin::ACCESS_READWRITE :
                        $rt->add('{' . Plugin::NS_CALENDARSERVER . '}shared');
                        break;

                }
            }
            $propFind->handle('{' . Plugin::NS_CALENDARSERVER . '}allowed-sharing-modes', function() {
                return new Xml\Property\AllowedSharingModes(true, false);
            });

        }

    }

    
    function propPatch($path, DAV\PropPatch $propPatch) {

        $node = $this->server->tree->getNodeForPath($path);
        if (!$node instanceof ISharedCalendar)
            return;

        if ($node->getShareAccess() === \Sabre\DAV\Sharing\Plugin::ACCESS_SHAREDOWNER || $node->getShareAccess() === \Sabre\DAV\Sharing\Plugin::ACCESS_NOTSHARED) {

            $propPatch->handle('{DAV:}resourcetype', function($value) use ($node) {
                if ($value->is('{' . Plugin::NS_CALENDARSERVER . '}shared-owner')) return false;
                $shares = $node->getInvites();
                foreach ($shares as $share) {
                    $share->access = DAV\Sharing\Plugin::ACCESS_NOACCESS;
                }
                $node->updateInvites($shares);

                return true;

            });

        }

    }

    
    function httpPost(RequestInterface $request, ResponseInterface $response) {

        $path = $request->getPath();

                $contentType = $request->getHeader('Content-Type');
        if (strpos($contentType, 'application/xml') === false && strpos($contentType, 'text/xml') === false)
            return;

                try {
            $node = $this->server->tree->getNodeForPath($path);
        } catch (DAV\Exception\NotFound $e) {
            return;
        }

        $requestBody = $request->getBodyAsString();

                                                                $request->setBody($requestBody);

        $message = $this->server->xml->parse($requestBody, $request->getUrl(), $documentType);

        switch ($documentType) {

                                    case '{' . Plugin::NS_CALENDARSERVER . '}share' :

                $sharingPlugin = $this->server->getPlugin('sharing');
                $sharingPlugin->shareResource($path, $message->sharees);

                $response->setStatus(200);
                                                $response->setHeader('X-Sabre-Status', 'everything-went-well');

                                return false;

                                    case '{' . Plugin::NS_CALENDARSERVER . '}invite-reply' :

                                if (!$node instanceof CalendarHome) {
                    return;
                }
                $this->server->transactionType = 'post-invite-reply';

                                $acl = $this->server->getPlugin('acl');

                                if ($acl) {
                    $acl->checkPrivileges($path, '{DAV:}write');
                }

                $url = $node->shareReply(
                    $message->href,
                    $message->status,
                    $message->calendarUri,
                    $message->inReplyTo,
                    $message->summary
                );

                $response->setStatus(200);
                                                $response->setHeader('X-Sabre-Status', 'everything-went-well');

                if ($url) {
                    $writer = $this->server->xml->getWriter();
                    $writer->openMemory();
                    $writer->startDocument();
                    $writer->startElement('{' . Plugin::NS_CALENDARSERVER . '}shared-as');
                    $writer->write(new LocalHref($url));
                    $writer->endElement();
                    $response->setHeader('Content-Type', 'application/xml');
                    $response->setBody($writer->outputMemory());

                }

                                return false;

            case '{' . Plugin::NS_CALENDARSERVER . '}publish-calendar' :

                                if (!$node instanceof ISharedCalendar) {
                    return;
                }
                $this->server->transactionType = 'post-publish-calendar';

                                $acl = $this->server->getPlugin('acl');

                                if ($acl) {
                    $acl->checkPrivileges($path, '{DAV:}share');
                }

                $node->setPublishStatus(true);

                                $response->setStatus(202);

                                                $response->setHeader('X-Sabre-Status', 'everything-went-well');

                                return false;

            case '{' . Plugin::NS_CALENDARSERVER . '}unpublish-calendar' :

                                if (!$node instanceof ISharedCalendar) {
                    return;
                }
                $this->server->transactionType = 'post-unpublish-calendar';

                                $acl = $this->server->getPlugin('acl');

                                if ($acl) {
                    $acl->checkPrivileges($path, '{DAV:}share');
                }

                $node->setPublishStatus(false);

                $response->setStatus(200);

                                                $response->setHeader('X-Sabre-Status', 'everything-went-well');

                                return false;

        }



    }

    
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'Adds support for caldav-sharing.',
            'link'        => 'http://sabre.io/dav/caldav-sharing/',
        ];

    }
}
