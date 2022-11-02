<?php

namespace Sabre\CalDAV\Schedule;

use DateTimeZone;
use Sabre\CalDAV\ICalendar;
use Sabre\CalDAV\ICalendarObject;
use Sabre\CalDAV\Xml\Property\ScheduleCalendarTransp;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\NotImplemented;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\Sharing;
use Sabre\DAV\Xml\Property\LocalHref;
use Sabre\DAVACL;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\ITip;
use Sabre\VObject\ITip\Message;
use Sabre\VObject\Reader;


class Plugin extends ServerPlugin {

    
    const NS_CALDAV = 'urn:ietf:params:xml:ns:caldav';

    
    protected $server;

    
    function getFeatures() {

        return ['calendar-auto-schedule', 'calendar-availability'];

    }

    
    function getPluginName() {

        return 'caldav-schedule';

    }

    
    function initialize(Server $server) {

        $this->server = $server;
        $server->on('method:POST',              [$this, 'httpPost']);
        $server->on('propFind',                 [$this, 'propFind']);
        $server->on('propPatch',                [$this, 'propPatch']);
        $server->on('calendarObjectChange',     [$this, 'calendarObjectChange']);
        $server->on('beforeUnbind',             [$this, 'beforeUnbind']);
        $server->on('schedule',                 [$this, 'scheduleLocalDelivery']);
        $server->on('getSupportedPrivilegeSet', [$this, 'getSupportedPrivilegeSet']);

        $ns = '{' . self::NS_CALDAV . '}';

        
        $server->resourceTypeMapping['\\Sabre\\CalDAV\\Schedule\\IOutbox'] = $ns . 'schedule-outbox';
        $server->resourceTypeMapping['\\Sabre\\CalDAV\\Schedule\\IInbox'] = $ns . 'schedule-inbox';

        
        array_push($server->protectedProperties,
            $ns . 'schedule-inbox-URL',
            $ns . 'schedule-outbox-URL',
            $ns . 'calendar-user-address-set',
            $ns . 'calendar-user-type',
            $ns . 'schedule-default-calendar-URL'
        );

    }

    
    function getHTTPMethods($uri) {

        try {
            $node = $this->server->tree->getNodeForPath($uri);
        } catch (NotFound $e) {
            return [];
        }

        if ($node instanceof IOutbox) {
            return ['POST'];
        }

        return [];

    }

    
    function httpPost(RequestInterface $request, ResponseInterface $response) {

                $contentType = $request->getHeader('Content-Type');
        if (strpos($contentType, 'text/calendar') !== 0) {
            return;
        }

        $path = $request->getPath();

                try {
            $node = $this->server->tree->getNodeForPath($path);
        } catch (NotFound $e) {
            return;
        }
        if (!$node instanceof IOutbox)
            return;

        $this->server->transactionType = 'post-caldav-outbox';
        $this->outboxRequest($node, $request, $response);

                        return false;

    }

    
    function propFind(PropFind $propFind, INode $node) {

        if ($node instanceof DAVACL\IPrincipal) {

            $caldavPlugin = $this->server->getPlugin('caldav');
            $principalUrl = $node->getPrincipalUrl();

                        $propFind->handle('{' . self::NS_CALDAV . '}schedule-outbox-URL', function() use ($principalUrl, $caldavPlugin) {

                $calendarHomePath = $caldavPlugin->getCalendarHomeForPrincipal($principalUrl);
                if (!$calendarHomePath) {
                    return null;
                }
                $outboxPath = $calendarHomePath . '/outbox/';

                return new LocalHref($outboxPath);

            });
                        $propFind->handle('{' . self::NS_CALDAV . '}schedule-inbox-URL', function() use ($principalUrl, $caldavPlugin) {

                $calendarHomePath = $caldavPlugin->getCalendarHomeForPrincipal($principalUrl);
                if (!$calendarHomePath) {
                    return null;
                }
                $inboxPath = $calendarHomePath . '/inbox/';

                return new LocalHref($inboxPath);

            });

            $propFind->handle('{' . self::NS_CALDAV . '}schedule-default-calendar-URL', function() use ($principalUrl, $caldavPlugin) {

                                                $calendarHomePath = $caldavPlugin->getCalendarHomeForPrincipal($principalUrl);

                if (!$calendarHomePath) {
                    return null;
                }

                $sccs = '{' . self::NS_CALDAV . '}supported-calendar-component-set';

                $result = $this->server->getPropertiesForPath($calendarHomePath, [
                    '{DAV:}resourcetype',
                    '{DAV:}share-access',
                    $sccs,
                ], 1);

                foreach ($result as $child) {
                    if (!isset($child[200]['{DAV:}resourcetype']) || !$child[200]['{DAV:}resourcetype']->is('{' . self::NS_CALDAV . '}calendar')) {
                                                continue;
                    }
                    if (isset($child[200]['{DAV:}share-access'])) {
                        $shareAccess = $child[200]['{DAV:}share-access']->getValue();
                        if ($shareAccess !== Sharing\Plugin::ACCESS_NOTSHARED && $shareAccess !== Sharing\Plugin::ACCESS_SHAREDOWNER) {
                                                                                    continue;
                        }

                    }
                    if (!isset($child[200][$sccs]) || in_array('VEVENT', $child[200][$sccs]->getValue())) {
                                                                        return new LocalHref($child['href']);
                    }
                }

            });

                                    $propFind->handle('{' . self::NS_CALDAV . '}calendar-user-type', function() {

                return 'INDIVIDUAL';

            });

        }

                $propFind->handle('{http://calendarserver.org/ns/}calendar-availability', function() use ($propFind, $node) {

                                      $availProp = '{' . self::NS_CALDAV . '}calendar-availability';
             $subPropFind = new PropFind(
                 $propFind->getPath(),
                 [$availProp]
             );

             $this->server->getPropertiesByNode(
                 $subPropFind,
                 $node
             );

             $propFind->set(
                 '{http://calendarserver.org/ns/}calendar-availability',
                 $subPropFind->get($availProp),
                 $subPropFind->getStatus($availProp)
             );

        });

    }

    
    function propPatch($path, PropPatch $propPatch) {

                $propPatch->handle('{http://calendarserver.org/ns/}calendar-availability', function($value) use ($path) {

            $availProp = '{' . self::NS_CALDAV . '}calendar-availability';
            $subPropPatch = new PropPatch([$availProp => $value]);
            $this->server->emit('propPatch', [$path, $subPropPatch]);
            $subPropPatch->commit();

            return $subPropPatch->getResult()[$availProp];

        });

    }

    
    function calendarObjectChange(RequestInterface $request, ResponseInterface $response, VCalendar $vCal, $calendarPath, &$modified, $isNew) {

        if (!$this->scheduleReply($this->server->httpRequest)) {
            return;
        }

        $calendarNode = $this->server->tree->getNodeForPath($calendarPath);

        $addresses = $this->getAddressesForPrincipal(
            $calendarNode->getOwner()
        );

        if (!$isNew) {
            $node = $this->server->tree->getNodeForPath($request->getPath());
            $oldObj = Reader::read($node->get());
        } else {
            $oldObj = null;
        }

        $this->processICalendarChange($oldObj, $vCal, $addresses, [], $modified);

        if ($oldObj) {
                        $oldObj->destroy();
        }

    }

    
    function deliver(ITip\Message $iTipMessage) {

        $this->server->emit('schedule', [$iTipMessage]);
        if (!$iTipMessage->scheduleStatus) {
            $iTipMessage->scheduleStatus = '5.2;There was no system capable of delivering the scheduling message';
        }
                        list($baseCode) = explode('.', $iTipMessage->scheduleStatus);
        if (!$iTipMessage->significantChange && in_array($baseCode, ['3', '5'])) {
            $iTipMessage->scheduleStatus = null;
        }

    }

    
    function beforeUnbind($path) {

                        if ($this->server->httpRequest->getMethod() === 'MOVE') return;

        $node = $this->server->tree->getNodeForPath($path);

        if (!$node instanceof ICalendarObject || $node instanceof ISchedulingObject) {
            return;
        }

        if (!$this->scheduleReply($this->server->httpRequest)) {
            return;
        }

        $addresses = $this->getAddressesForPrincipal(
            $node->getOwner()
        );

        $broker = new ITip\Broker();
        $messages = $broker->parseEvent(null, $addresses, $node->get());

        foreach ($messages as $message) {
            $this->deliver($message);
        }

    }

    
    function scheduleLocalDelivery(ITip\Message $iTipMessage) {

        $aclPlugin = $this->server->getPlugin('acl');

                if (!$aclPlugin) {
            return;
        }

        $caldavNS = '{' . self::NS_CALDAV . '}';

        $principalUri = $aclPlugin->getPrincipalByUri($iTipMessage->recipient);
        if (!$principalUri) {
            $iTipMessage->scheduleStatus = '3.7;Could not find principal.';
            return;
        }

                                                        $this->server->removeListener('propFind', [$aclPlugin, 'propFind']);

        $result = $this->server->getProperties(
            $principalUri,
            [
                '{DAV:}principal-URL',
                 $caldavNS . 'calendar-home-set',
                 $caldavNS . 'schedule-inbox-URL',
                 $caldavNS . 'schedule-default-calendar-URL',
                '{http://sabredav.org/ns}email-address',
            ]
        );

                $this->server->on('propFind', [$aclPlugin, 'propFind'], 20);

        if (!isset($result[$caldavNS . 'schedule-inbox-URL'])) {
            $iTipMessage->scheduleStatus = '5.2;Could not find local inbox';
            return;
        }
        if (!isset($result[$caldavNS . 'calendar-home-set'])) {
            $iTipMessage->scheduleStatus = '5.2;Could not locate a calendar-home-set';
            return;
        }
        if (!isset($result[$caldavNS . 'schedule-default-calendar-URL'])) {
            $iTipMessage->scheduleStatus = '5.2;Could not find a schedule-default-calendar-URL property';
            return;
        }

        $calendarPath = $result[$caldavNS . 'schedule-default-calendar-URL']->getHref();
        $homePath = $result[$caldavNS . 'calendar-home-set']->getHref();
        $inboxPath = $result[$caldavNS . 'schedule-inbox-URL']->getHref();

        if ($iTipMessage->method === 'REPLY') {
            $privilege = 'schedule-deliver-reply';
        } else {
            $privilege = 'schedule-deliver-invite';
        }

        if (!$aclPlugin->checkPrivileges($inboxPath, $caldavNS . $privilege, DAVACL\Plugin::R_PARENT, false)) {
            $iTipMessage->scheduleStatus = '3.8;insufficient privileges: ' . $privilege . ' is required on the recipient schedule inbox.';
            return;
        }

                        $uid = $iTipMessage->uid;

        $newFileName = 'sabredav-' . \Sabre\DAV\UUIDUtil::getUUID() . '.ics';

        $home = $this->server->tree->getNodeForPath($homePath);
        $inbox = $this->server->tree->getNodeForPath($inboxPath);

        $currentObject = null;
        $objectNode = null;
        $isNewNode = false;

        $result = $home->getCalendarObjectByUID($uid);
        if ($result) {
                        $objectPath = $homePath . '/' . $result;
            $objectNode = $this->server->tree->getNodeForPath($objectPath);
            $oldICalendarData = $objectNode->get();
            $currentObject = Reader::read($oldICalendarData);
        } else {
            $isNewNode = true;
        }

        $broker = new ITip\Broker();
        $newObject = $broker->processMessage($iTipMessage, $currentObject);

        $inbox->createFile($newFileName, $iTipMessage->message->serialize());

        if (!$newObject) {
                                                                                    $iTipMessage->scheduleStatus = '5.0;iTip message was not processed by the server, likely because we didn\'t understand it.';
            return;
        }

                                if ($isNewNode) {
            $calendar = $this->server->tree->getNodeForPath($calendarPath);
            $calendar->createFile($newFileName, $newObject->serialize());
        } else {
                                                if ($iTipMessage->method === 'REPLY') {
                $this->processICalendarChange(
                    $oldICalendarData,
                    $newObject,
                    [$iTipMessage->recipient],
                    [$iTipMessage->sender]
                );
            }
            $objectNode->put($newObject->serialize());
        }
        $iTipMessage->scheduleStatus = '1.2;Message delivered locally';

    }

    
    function getSupportedPrivilegeSet(INode $node, array &$supportedPrivilegeSet) {

        $ns = '{' . self::NS_CALDAV . '}';
        if ($node instanceof IOutbox) {
            $supportedPrivilegeSet[$ns . 'schedule-send'] = [
                'abstract'   => false,
                'aggregates' => [
                    $ns . 'schedule-send-invite' => [
                        'abstract'   => false,
                        'aggregates' => [],
                    ],
                    $ns . 'schedule-send-reply' => [
                        'abstract'   => false,
                        'aggregates' => [],
                    ],
                    $ns . 'schedule-send-freebusy' => [
                        'abstract'   => false,
                        'aggregates' => [],
                    ],
                                                            $ns . 'schedule-post-vevent' => [
                        'abstract'   => false,
                        'aggregates' => [],
                    ],
                ]
            ];
        }
        if ($node instanceof IInbox) {
            $supportedPrivilegeSet[$ns . 'schedule-deliver'] = [
                'abstract'   => false,
                'aggregates' => [
                    $ns . 'schedule-deliver-invite' => [
                        'abstract'   => false,
                        'aggregates' => [],
                    ],
                    $ns . 'schedule-deliver-reply' => [
                        'abstract'   => false,
                        'aggregates' => [],
                    ],
                    $ns . 'schedule-query-freebusy' => [
                        'abstract'   => false,
                        'aggregates' => [],
                    ],
                ]
            ];
        }

    }

    
    protected function processICalendarChange($oldObject = null, VCalendar $newObject, array $addresses, array $ignore = [], &$modified = false) {

        $broker = new ITip\Broker();
        $messages = $broker->parseEvent($newObject, $addresses, $oldObject);

        if ($messages) $modified = true;

        foreach ($messages as $message) {

            if (in_array($message->recipient, $ignore)) {
                continue;
            }

            $this->deliver($message);

            if (isset($newObject->VEVENT->ORGANIZER) && ($newObject->VEVENT->ORGANIZER->getNormalizedValue() === $message->recipient)) {
                if ($message->scheduleStatus) {
                    $newObject->VEVENT->ORGANIZER['SCHEDULE-STATUS'] = $message->getScheduleStatus();
                }
                unset($newObject->VEVENT->ORGANIZER['SCHEDULE-FORCE-SEND']);

            } else {

                if (isset($newObject->VEVENT->ATTENDEE)) foreach ($newObject->VEVENT->ATTENDEE as $attendee) {

                    if ($attendee->getNormalizedValue() === $message->recipient) {
                        if ($message->scheduleStatus) {
                            $attendee['SCHEDULE-STATUS'] = $message->getScheduleStatus();
                        }
                        unset($attendee['SCHEDULE-FORCE-SEND']);
                        break;
                    }

                }

            }

        }

    }

    
    protected function getAddressesForPrincipal($principal) {

        $CUAS = '{' . self::NS_CALDAV . '}calendar-user-address-set';

        $properties = $this->server->getProperties(
            $principal,
            [$CUAS]
        );

                if (!isset($properties[$CUAS])) {
            return;
        }

        $addresses = $properties[$CUAS]->getHrefs();
        return $addresses;

    }

    
    function outboxRequest(IOutbox $outboxNode, RequestInterface $request, ResponseInterface $response) {

        $outboxPath = $request->getPath();

                try {
            $vObject = VObject\Reader::read($request->getBody());
        } catch (VObject\ParseException $e) {
            throw new BadRequest('The request body must be a valid iCalendar object. Parse error: ' . $e->getMessage());
        }

                                $componentType = null;
        foreach ($vObject->getComponents() as $component) {
            if ($component->name !== 'VTIMEZONE') {
                $componentType = $component->name;
                break;
            }
        }
        if (is_null($componentType)) {
            throw new BadRequest('We expected at least one VTODO, VJOURNAL, VFREEBUSY or VEVENT component');
        }

                $method = strtoupper((string)$vObject->METHOD);
        if (!$method) {
            throw new BadRequest('A METHOD property must be specified in iTIP messages');
        }

                        
        $acl = $this->server->getPlugin('acl');

        if ($componentType === 'VFREEBUSY' && $method === 'REQUEST') {

            $acl && $acl->checkPrivileges($outboxPath, '{' . self::NS_CALDAV . '}schedule-send-freebusy');
            $this->handleFreeBusyRequest($outboxNode, $vObject, $request, $response);

                        $vObject->destroy();
            unset($vObject);

        } else {

            throw new NotImplemented('We only support VFREEBUSY (REQUEST) on this endpoint');

        }

    }

    
    protected function handleFreeBusyRequest(IOutbox $outbox, VObject\Component $vObject, RequestInterface $request, ResponseInterface $response) {

        $vFreeBusy = $vObject->VFREEBUSY;
        $organizer = $vFreeBusy->ORGANIZER;

        $organizer = (string)$organizer;

                $owner = $outbox->getOwner();

        $caldavNS = '{' . self::NS_CALDAV . '}';

        $uas = $caldavNS . 'calendar-user-address-set';
        $props = $this->server->getProperties($owner, [$uas]);

        if (empty($props[$uas]) || !in_array($organizer, $props[$uas]->getHrefs())) {
            throw new Forbidden('The organizer in the request did not match any of the addresses for the owner of this inbox');
        }

        if (!isset($vFreeBusy->ATTENDEE)) {
            throw new BadRequest('You must at least specify 1 attendee');
        }

        $attendees = [];
        foreach ($vFreeBusy->ATTENDEE as $attendee) {
            $attendees[] = (string)$attendee;
        }


        if (!isset($vFreeBusy->DTSTART) || !isset($vFreeBusy->DTEND)) {
            throw new BadRequest('DTSTART and DTEND must both be specified');
        }

        $startRange = $vFreeBusy->DTSTART->getDateTime();
        $endRange = $vFreeBusy->DTEND->getDateTime();

        $results = [];
        foreach ($attendees as $attendee) {
            $results[] = $this->getFreeBusyForEmail($attendee, $startRange, $endRange, $vObject);
        }

        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;
        $scheduleResponse = $dom->createElement('cal:schedule-response');
        foreach ($this->server->xml->namespaceMap as $namespace => $prefix) {

            $scheduleResponse->setAttribute('xmlns:' . $prefix, $namespace);

        }
        $dom->appendChild($scheduleResponse);

        foreach ($results as $result) {
            $xresponse = $dom->createElement('cal:response');

            $recipient = $dom->createElement('cal:recipient');
            $recipientHref = $dom->createElement('d:href');

            $recipientHref->appendChild($dom->createTextNode($result['href']));
            $recipient->appendChild($recipientHref);
            $xresponse->appendChild($recipient);

            $reqStatus = $dom->createElement('cal:request-status');
            $reqStatus->appendChild($dom->createTextNode($result['request-status']));
            $xresponse->appendChild($reqStatus);

            if (isset($result['calendar-data'])) {

                $calendardata = $dom->createElement('cal:calendar-data');
                $calendardata->appendChild($dom->createTextNode(str_replace("\r\n", "\n", $result['calendar-data']->serialize())));
                $xresponse->appendChild($calendardata);

            }
            $scheduleResponse->appendChild($xresponse);
        }

        $response->setStatus(200);
        $response->setHeader('Content-Type', 'application/xml');
        $response->setBody($dom->saveXML());

    }

    
    protected function getFreeBusyForEmail($email, \DateTimeInterface $start, \DateTimeInterface $end, VObject\Component $request) {

        $caldavNS = '{' . self::NS_CALDAV . '}';

        $aclPlugin = $this->server->getPlugin('acl');
        if (substr($email, 0, 7) === 'mailto:') $email = substr($email, 7);

        $result = $aclPlugin->principalSearch(
            ['{http://sabredav.org/ns}email-address' => $email],
            [
                '{DAV:}principal-URL',
                $caldavNS . 'calendar-home-set',
                $caldavNS . 'schedule-inbox-URL',
                '{http://sabredav.org/ns}email-address',

            ]
        );

        if (!count($result)) {
            return [
                'request-status' => '3.7;Could not find principal',
                'href'           => 'mailto:' . $email,
            ];
        }

        if (!isset($result[0][200][$caldavNS . 'calendar-home-set'])) {
            return [
                'request-status' => '3.7;No calendar-home-set property found',
                'href'           => 'mailto:' . $email,
            ];
        }
        if (!isset($result[0][200][$caldavNS . 'schedule-inbox-URL'])) {
            return [
                'request-status' => '3.7;No schedule-inbox-URL property found',
                'href'           => 'mailto:' . $email,
            ];
        }
        $homeSet = $result[0][200][$caldavNS . 'calendar-home-set']->getHref();
        $inboxUrl = $result[0][200][$caldavNS . 'schedule-inbox-URL']->getHref();

                $aclPlugin->checkPrivileges($inboxUrl, $caldavNS . 'schedule-query-freebusy');

                $objects = [];
        $calendarTimeZone = new DateTimeZone('UTC');

        foreach ($this->server->tree->getNodeForPath($homeSet)->getChildren() as $node) {
            if (!$node instanceof ICalendar) {
                continue;
            }

            $sct = $caldavNS . 'schedule-calendar-transp';
            $ctz = $caldavNS . 'calendar-timezone';
            $props = $node->getProperties([$sct, $ctz]);

            if (isset($props[$sct]) && $props[$sct]->getValue() == ScheduleCalendarTransp::TRANSPARENT) {
                                                continue;
            }

            if (isset($props[$ctz])) {
                $vtimezoneObj = VObject\Reader::read($props[$ctz]);
                $calendarTimeZone = $vtimezoneObj->VTIMEZONE->getTimeZone();

                                $vtimezoneObj->destroy();

            }

                        $urls = $node->calendarQuery([
                'name'         => 'VCALENDAR',
                'comp-filters' => [
                    [
                        'name'           => 'VEVENT',
                        'comp-filters'   => [],
                        'prop-filters'   => [],
                        'is-not-defined' => false,
                        'time-range'     => [
                            'start' => $start,
                            'end'   => $end,
                        ],
                    ],
                ],
                'prop-filters'   => [],
                'is-not-defined' => false,
                'time-range'     => null,
            ]);

            $calObjects = array_map(function($url) use ($node) {
                $obj = $node->getChild($url)->get();
                return $obj;
            }, $urls);

            $objects = array_merge($objects, $calObjects);

        }

        $inboxProps = $this->server->getProperties(
            $inboxUrl,
            $caldavNS . 'calendar-availability'
        );

        $vcalendar = new VObject\Component\VCalendar();
        $vcalendar->METHOD = 'REPLY';

        $generator = new VObject\FreeBusyGenerator();
        $generator->setObjects($objects);
        $generator->setTimeRange($start, $end);
        $generator->setBaseObject($vcalendar);
        $generator->setTimeZone($calendarTimeZone);

        if ($inboxProps) {
            $generator->setVAvailability(
                VObject\Reader::read(
                    $inboxProps[$caldavNS . 'calendar-availability']
                )
            );
        }

        $result = $generator->getResult();

        $vcalendar->VFREEBUSY->ATTENDEE = 'mailto:' . $email;
        $vcalendar->VFREEBUSY->UID = (string)$request->VFREEBUSY->UID;
        $vcalendar->VFREEBUSY->ORGANIZER = clone $request->VFREEBUSY->ORGANIZER;

        return [
            'calendar-data'  => $result,
            'request-status' => '2.0;Success',
            'href'           => 'mailto:' . $email,
        ];
    }

    
    private function scheduleReply(RequestInterface $request) {

        $scheduleReply = $request->getHeader('Schedule-Reply');
        return $scheduleReply !== 'F';

    }

    
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'Adds calendar-auto-schedule, as defined in rfc6638',
            'link'        => 'http://sabre.io/dav/scheduling/',
        ];

    }
}
