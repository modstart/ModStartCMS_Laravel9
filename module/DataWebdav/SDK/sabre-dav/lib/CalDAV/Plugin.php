<?php

namespace Sabre\CalDAV;

use DateTimeZone;
use Sabre\DAV;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\INode;
use Sabre\DAV\MkCol;
use Sabre\DAV\Xml\Property\LocalHref;
use Sabre\DAVACL;
use Sabre\HTTP;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\Uri;
use Sabre\VObject;


class Plugin extends DAV\ServerPlugin {

    
    const NS_CALDAV = 'urn:ietf:params:xml:ns:caldav';

    
    const NS_CALENDARSERVER = 'http://calendarserver.org/ns/';

    
    const CALENDAR_ROOT = 'calendars';

    
    protected $server;

    
    protected $maxResourceSize = 10000000;

    
    function getHTTPMethods($uri) {

                        list($parent, $name) = Uri\split($uri);

        $node = $this->server->tree->getNodeForPath($parent);

        if ($node instanceof DAV\IExtendedCollection) {
            try {
                $node->getChild($name);
            } catch (DAV\Exception\NotFound $e) {
                return ['MKCALENDAR'];
            }
        }
        return [];

    }

    
    function getCalendarHomeForPrincipal($principalUrl) {

                                                                $parts = explode('/', trim($principalUrl, '/'));
        if (count($parts) !== 2) return;
        if ($parts[0] !== 'principals') return;

        return self::CALENDAR_ROOT . '/' . $parts[1];

    }

    
    function getFeatures() {

        return ['calendar-access', 'calendar-proxy'];

    }

    
    function getPluginName() {

        return 'caldav';

    }

    
    function getSupportedReportSet($uri) {

        $node = $this->server->tree->getNodeForPath($uri);

        $reports = [];
        if ($node instanceof ICalendarObjectContainer || $node instanceof ICalendarObject) {
            $reports[] = '{' . self::NS_CALDAV . '}calendar-multiget';
            $reports[] = '{' . self::NS_CALDAV . '}calendar-query';
        }
        if ($node instanceof ICalendar) {
            $reports[] = '{' . self::NS_CALDAV . '}free-busy-query';
        }
                                if ($node instanceof CalendarHome && $this->server->getPlugin('sync')) {
            $reports[] = '{DAV:}sync-collection';
        }
        return $reports;

    }

    
    function initialize(DAV\Server $server) {

        $this->server = $server;

        $server->on('method:MKCALENDAR',   [$this, 'httpMkCalendar']);
        $server->on('report',              [$this, 'report']);
        $server->on('propFind',            [$this, 'propFind']);
        $server->on('onHTMLActionsPanel',  [$this, 'htmlActionsPanel']);
        $server->on('beforeCreateFile',    [$this, 'beforeCreateFile']);
        $server->on('beforeWriteContent',  [$this, 'beforeWriteContent']);
        $server->on('afterMethod:GET',     [$this, 'httpAfterGET']);
        $server->on('getSupportedPrivilegeSet', [$this, 'getSupportedPrivilegeSet']);

        $server->xml->namespaceMap[self::NS_CALDAV] = 'cal';
        $server->xml->namespaceMap[self::NS_CALENDARSERVER] = 'cs';

        $server->xml->elementMap['{' . self::NS_CALDAV . '}supported-calendar-component-set'] = 'Sabre\\CalDAV\\Xml\\Property\\SupportedCalendarComponentSet';
        $server->xml->elementMap['{' . self::NS_CALDAV . '}calendar-query'] = 'Sabre\\CalDAV\\Xml\\Request\\CalendarQueryReport';
        $server->xml->elementMap['{' . self::NS_CALDAV . '}calendar-multiget'] = 'Sabre\\CalDAV\\Xml\\Request\\CalendarMultiGetReport';
        $server->xml->elementMap['{' . self::NS_CALDAV . '}free-busy-query'] = 'Sabre\\CalDAV\\Xml\\Request\\FreeBusyQueryReport';
        $server->xml->elementMap['{' . self::NS_CALDAV . '}mkcalendar'] = 'Sabre\\CalDAV\\Xml\\Request\\MkCalendar';
        $server->xml->elementMap['{' . self::NS_CALDAV . '}schedule-calendar-transp'] = 'Sabre\\CalDAV\\Xml\\Property\\ScheduleCalendarTransp';
        $server->xml->elementMap['{' . self::NS_CALDAV . '}supported-calendar-component-set'] = 'Sabre\\CalDAV\\Xml\\Property\\SupportedCalendarComponentSet';

        $server->resourceTypeMapping['\\Sabre\\CalDAV\\ICalendar'] = '{urn:ietf:params:xml:ns:caldav}calendar';

        $server->resourceTypeMapping['\\Sabre\\CalDAV\\Principal\\IProxyRead'] = '{http://calendarserver.org/ns/}calendar-proxy-read';
        $server->resourceTypeMapping['\\Sabre\\CalDAV\\Principal\\IProxyWrite'] = '{http://calendarserver.org/ns/}calendar-proxy-write';

        array_push($server->protectedProperties,

            '{' . self::NS_CALDAV . '}supported-calendar-component-set',
            '{' . self::NS_CALDAV . '}supported-calendar-data',
            '{' . self::NS_CALDAV . '}max-resource-size',
            '{' . self::NS_CALDAV . '}min-date-time',
            '{' . self::NS_CALDAV . '}max-date-time',
            '{' . self::NS_CALDAV . '}max-instances',
            '{' . self::NS_CALDAV . '}max-attendees-per-instance',
            '{' . self::NS_CALDAV . '}calendar-home-set',
            '{' . self::NS_CALDAV . '}supported-collation-set',
            '{' . self::NS_CALDAV . '}calendar-data',

                        '{' . self::NS_CALENDARSERVER . '}getctag',
            '{' . self::NS_CALENDARSERVER . '}calendar-proxy-read-for',
            '{' . self::NS_CALENDARSERVER . '}calendar-proxy-write-for'

        );

        if ($aclPlugin = $server->getPlugin('acl')) {
            $aclPlugin->principalSearchPropertySet['{' . self::NS_CALDAV . '}calendar-user-address-set'] = 'Calendar address';
        }
    }

    
    function report($reportName, $report, $path) {

        switch ($reportName) {
            case '{' . self::NS_CALDAV . '}calendar-multiget' :
                $this->server->transactionType = 'report-calendar-multiget';
                $this->calendarMultiGetReport($report);
                return false;
            case '{' . self::NS_CALDAV . '}calendar-query' :
                $this->server->transactionType = 'report-calendar-query';
                $this->calendarQueryReport($report);
                return false;
            case '{' . self::NS_CALDAV . '}free-busy-query' :
                $this->server->transactionType = 'report-free-busy-query';
                $this->freeBusyQueryReport($report);
                return false;

        }


    }

    
    function httpMkCalendar(RequestInterface $request, ResponseInterface $response) {

        $body = $request->getBodyAsString();
        $path = $request->getPath();

        $properties = [];

        if ($body) {

            try {
                $mkcalendar = $this->server->xml->expect(
                    '{urn:ietf:params:xml:ns:caldav}mkcalendar',
                    $body
                );
            } catch (\Sabre\Xml\ParseException $e) {
                throw new BadRequest($e->getMessage(), null, $e);
            }
            $properties = $mkcalendar->getProperties();

        }

                                                        if (isset($properties['{DAV:}resourcetype'])) {
            $resourceType = $properties['{DAV:}resourcetype']->getValue();
        } else {
            $resourceType = ['{DAV:}collection','{urn:ietf:params:xml:ns:caldav}calendar'];
        }

        $this->server->createCollection($path, new MkCol($resourceType, $properties));

        $response->setStatus(201);
        $response->setHeader('Content-Length', 0);

                return false;
    }

    
    function propFind(DAV\PropFind $propFind, DAV\INode $node) {

        $ns = '{' . self::NS_CALDAV . '}';

        if ($node instanceof ICalendarObjectContainer) {

            $propFind->handle($ns . 'max-resource-size', $this->maxResourceSize);
            $propFind->handle($ns . 'supported-calendar-data', function() {
                return new Xml\Property\SupportedCalendarData();
            });
            $propFind->handle($ns . 'supported-collation-set', function() {
                return new Xml\Property\SupportedCollationSet();
            });

        }

        if ($node instanceof DAVACL\IPrincipal) {

            $principalUrl = $node->getPrincipalUrl();

            $propFind->handle('{' . self::NS_CALDAV . '}calendar-home-set', function() use ($principalUrl) {

                $calendarHomePath = $this->getCalendarHomeForPrincipal($principalUrl);
                if (is_null($calendarHomePath)) return null;
                return new LocalHref($calendarHomePath . '/');

            });
                                    $propFind->handle('{' . self::NS_CALDAV . '}calendar-user-address-set', function() use ($node) {
                $addresses = $node->getAlternateUriSet();
                $addresses[] = $this->server->getBaseUri() . $node->getPrincipalUrl() . '/';
                return new LocalHref($addresses);
            });
                                    $propFind->handle('{' . self::NS_CALENDARSERVER . '}email-address-set', function() use ($node) {
                $addresses = $node->getAlternateUriSet();
                $emails = [];
                foreach ($addresses as $address) {
                    if (substr($address, 0, 7) === 'mailto:') {
                        $emails[] = substr($address, 7);
                    }
                }
                return new Xml\Property\EmailAddressSet($emails);
            });

                                    $propRead = '{' . self::NS_CALENDARSERVER . '}calendar-proxy-read-for';
            $propWrite = '{' . self::NS_CALENDARSERVER . '}calendar-proxy-write-for';

            if ($propFind->getStatus($propRead) === 404 || $propFind->getStatus($propWrite) === 404) {

                $aclPlugin = $this->server->getPlugin('acl');
                $membership = $aclPlugin->getPrincipalMembership($propFind->getPath());
                $readList = [];
                $writeList = [];

                foreach ($membership as $group) {

                    $groupNode = $this->server->tree->getNodeForPath($group);

                    $listItem = Uri\split($group)[0] . '/';

                                                                                if ($groupNode instanceof Principal\IProxyRead) {
                        $readList[] = $listItem;
                    }
                    if ($groupNode instanceof Principal\IProxyWrite) {
                        $writeList[] = $listItem;
                    }

                }

                $propFind->set($propRead, new LocalHref($readList));
                $propFind->set($propWrite, new LocalHref($writeList));

            }

        } 
        if ($node instanceof ICalendarObject) {

                                                $propFind->handle('{' . self::NS_CALDAV . '}calendar-data', function() use ($node) {
                $val = $node->get();
                if (is_resource($val))
                    $val = stream_get_contents($val);

                                return str_replace("\r", "", $val);

            });

        }

    }

    
    function calendarMultiGetReport($report) {

        $needsJson = $report->contentType === 'application/calendar+json';

        $timeZones = [];
        $propertyList = [];

        $paths = array_map(
            [$this->server, 'calculateUri'],
            $report->hrefs
        );

        foreach ($this->server->getPropertiesForMultiplePaths($paths, $report->properties) as $uri => $objProps) {

            if (($needsJson || $report->expand) && isset($objProps[200]['{' . self::NS_CALDAV . '}calendar-data'])) {
                $vObject = VObject\Reader::read($objProps[200]['{' . self::NS_CALDAV . '}calendar-data']);

                if ($report->expand) {
                                                            list($calendarPath) = Uri\split($uri);
                    if (!isset($timeZones[$calendarPath])) {
                                                $tzProp = '{' . self::NS_CALDAV . '}calendar-timezone';
                        $tzResult = $this->server->getProperties($calendarPath, [$tzProp]);
                        if (isset($tzResult[$tzProp])) {
                                                                                    $vtimezoneObj = VObject\Reader::read($tzResult[$tzProp]);
                            $timeZone = $vtimezoneObj->VTIMEZONE->getTimeZone();
                        } else {
                                                        $timeZone = new DateTimeZone('UTC');
                        }
                        $timeZones[$calendarPath] = $timeZone;
                    }

                    $vObject = $vObject->expand($report->expand['start'], $report->expand['end'], $timeZones[$calendarPath]);
                }
                if ($needsJson) {
                    $objProps[200]['{' . self::NS_CALDAV . '}calendar-data'] = json_encode($vObject->jsonSerialize());
                } else {
                    $objProps[200]['{' . self::NS_CALDAV . '}calendar-data'] = $vObject->serialize();
                }
                                                $vObject->destroy();
            }

            $propertyList[] = $objProps;

        }

        $prefer = $this->server->getHTTPPrefer();

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary', 'Brief,Prefer');
        $this->server->httpResponse->setBody($this->server->generateMultiStatus($propertyList, $prefer['return'] === 'minimal'));

    }

    
    function calendarQueryReport($report) {

        $path = $this->server->getRequestUri();

        $needsJson = $report->contentType === 'application/calendar+json';

        $node = $this->server->tree->getNodeForPath($this->server->getRequestUri());
        $depth = $this->server->getHTTPDepth(0);

                $result = [];

        $calendarTimeZone = null;
        if ($report->expand) {
                                    $tzProp = '{' . self::NS_CALDAV . '}calendar-timezone';
            $tzResult = $this->server->getProperties($path, [$tzProp]);
            if (isset($tzResult[$tzProp])) {
                                                $vtimezoneObj = VObject\Reader::read($tzResult[$tzProp]);
                $calendarTimeZone = $vtimezoneObj->VTIMEZONE->getTimeZone();

                                                $vtimezoneObj->destroy();
            } else {
                                $calendarTimeZone = new DateTimeZone('UTC');
            }
        }

                        if ($depth == 0 && $node instanceof ICalendarObject) {

            $requestedCalendarData = true;
            $requestedProperties = $report->properties;

            if (!in_array('{urn:ietf:params:xml:ns:caldav}calendar-data', $requestedProperties)) {

                                $requestedProperties[] = '{urn:ietf:params:xml:ns:caldav}calendar-data';

                                                $requestedCalendarData = false;
            }

            $properties = $this->server->getPropertiesForPath(
                $path,
                $requestedProperties,
                0
            );

                                    $properties = current($properties);

                                    if (isset($properties[200]['{urn:ietf:params:xml:ns:caldav}calendar-data'])) {

                $validator = new CalendarQueryValidator();

                $vObject = VObject\Reader::read($properties[200]['{urn:ietf:params:xml:ns:caldav}calendar-data']);
                if ($validator->validate($vObject, $report->filters)) {

                                                            if (!$requestedCalendarData) {
                        unset($properties[200]['{urn:ietf:params:xml:ns:caldav}calendar-data']);
                    } else {


                        if ($report->expand) {
                            $vObject = $vObject->expand($report->expand['start'], $report->expand['end'], $calendarTimeZone);
                        }
                        if ($needsJson) {
                            $properties[200]['{' . self::NS_CALDAV . '}calendar-data'] = json_encode($vObject->jsonSerialize());
                        } elseif ($report->expand) {
                            $properties[200]['{' . self::NS_CALDAV . '}calendar-data'] = $vObject->serialize();
                        }
                    }

                    $result = [$properties];

                }
                                                $vObject->destroy();

            }

        }

        if ($node instanceof ICalendarObjectContainer && $depth === 0) {

            if (strpos($this->server->httpRequest->getHeader('User-Agent'), 'MSFT-') === 0) {
                                                                                                                                $depth = 1;
            } else {
                throw new BadRequest('A calendar-query REPORT on a calendar with a Depth: 0 is undefined. Set Depth to 1');
            }

        }

                        if ($node instanceof ICalendarObjectContainer && $depth == 1) {

            $nodePaths = $node->calendarQuery($report->filters);

            foreach ($nodePaths as $path) {

                list($properties) =
                    $this->server->getPropertiesForPath($this->server->getRequestUri() . '/' . $path, $report->properties);

                if (($needsJson || $report->expand)) {
                    $vObject = VObject\Reader::read($properties[200]['{' . self::NS_CALDAV . '}calendar-data']);

                    if ($report->expand) {
                        $vObject = $vObject->expand($report->expand['start'], $report->expand['end'], $calendarTimeZone);
                    }

                    if ($needsJson) {
                        $properties[200]['{' . self::NS_CALDAV . '}calendar-data'] = json_encode($vObject->jsonSerialize());
                    } else {
                        $properties[200]['{' . self::NS_CALDAV . '}calendar-data'] = $vObject->serialize();
                    }

                                                            $vObject->destroy();
                }
                $result[] = $properties;

            }

        }

        $prefer = $this->server->getHTTPPrefer();

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary', 'Brief,Prefer');
        $this->server->httpResponse->setBody($this->server->generateMultiStatus($result, $prefer['return'] === 'minimal'));

    }

    
    protected function freeBusyQueryReport(Xml\Request\FreeBusyQueryReport $report) {

        $uri = $this->server->getRequestUri();

        $acl = $this->server->getPlugin('acl');
        if ($acl) {
            $acl->checkPrivileges($uri, '{' . self::NS_CALDAV . '}read-free-busy');
        }

        $calendar = $this->server->tree->getNodeForPath($uri);
        if (!$calendar instanceof ICalendar) {
            throw new DAV\Exception\NotImplemented('The free-busy-query REPORT is only implemented on calendars');
        }

        $tzProp = '{' . self::NS_CALDAV . '}calendar-timezone';

                        $calendarProps = $this->server->getProperties($uri, [$tzProp]);

        if (isset($calendarProps[$tzProp])) {
            $vtimezoneObj = VObject\Reader::read($calendarProps[$tzProp]);
            $calendarTimeZone = $vtimezoneObj->VTIMEZONE->getTimeZone();
                        $vtimezoneObj->destroy();
        } else {
            $calendarTimeZone = new DateTimeZone('UTC');
        }

                        $urls = $calendar->calendarQuery([
            'name'         => 'VCALENDAR',
            'comp-filters' => [
                [
                    'name'           => 'VEVENT',
                    'comp-filters'   => [],
                    'prop-filters'   => [],
                    'is-not-defined' => false,
                    'time-range'     => [
                        'start' => $report->start,
                        'end'   => $report->end,
                    ],
                ],
            ],
            'prop-filters'   => [],
            'is-not-defined' => false,
            'time-range'     => null,
        ]);

        $objects = array_map(function($url) use ($calendar) {
            $obj = $calendar->getChild($url)->get();
            return $obj;
        }, $urls);

        $generator = new VObject\FreeBusyGenerator();
        $generator->setObjects($objects);
        $generator->setTimeRange($report->start, $report->end);
        $generator->setTimeZone($calendarTimeZone);
        $result = $generator->getResult();
        $result = $result->serialize();

        $this->server->httpResponse->setStatus(200);
        $this->server->httpResponse->setHeader('Content-Type', 'text/calendar');
        $this->server->httpResponse->setHeader('Content-Length', strlen($result));
        $this->server->httpResponse->setBody($result);

    }

    
    function beforeWriteContent($path, DAV\IFile $node, &$data, &$modified) {

        if (!$node instanceof ICalendarObject)
            return;

                                list($parent) = Uri\split($path);
        $parentNode = $this->server->tree->getNodeForPath($parent);

        if (!$parentNode instanceof ICalendar)
            return;

        $this->validateICalendar(
            $data,
            $path,
            $modified,
            $this->server->httpRequest,
            $this->server->httpResponse,
            false
        );

    }

    
    function beforeCreateFile($path, &$data, DAV\ICollection $parentNode, &$modified) {

        if (!$parentNode instanceof ICalendar)
            return;

        $this->validateICalendar(
            $data,
            $path,
            $modified,
            $this->server->httpRequest,
            $this->server->httpResponse,
            true
        );

    }

    
    protected function validateICalendar(&$data, $path, &$modified, RequestInterface $request, ResponseInterface $response, $isNew) {

                if (is_resource($data)) {
            $data = stream_get_contents($data);
        }

        $before = $data;

        try {

                                    if (substr($data, 0, 1) === '[') {
                $vobj = VObject\Reader::readJson($data);

                                                $data = $vobj->serialize();
                $modified = true;
            } else {
                $vobj = VObject\Reader::read($data);
            }

        } catch (VObject\ParseException $e) {

            throw new DAV\Exception\UnsupportedMediaType('This resource only supports valid iCalendar 2.0 data. Parse error: ' . $e->getMessage());

        }

        if ($vobj->name !== 'VCALENDAR') {
            throw new DAV\Exception\UnsupportedMediaType('This collection can only support iCalendar objects.');
        }

        $sCCS = '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set';

                list($parentPath) = Uri\split($path);
        $calendarProperties = $this->server->getProperties($parentPath, [$sCCS]);

        if (isset($calendarProperties[$sCCS])) {
            $supportedComponents = $calendarProperties[$sCCS]->getValue();
        } else {
            $supportedComponents = ['VJOURNAL', 'VTODO', 'VEVENT'];
        }

        $foundType = null;

        foreach ($vobj->getComponents() as $component) {
            switch ($component->name) {
                case 'VTIMEZONE' :
                    continue 2;
                case 'VEVENT' :
                case 'VTODO' :
                case 'VJOURNAL' :
                    $foundType = $component->name;
                    break;
            }

        }

        if (!$foundType || !in_array($foundType, $supportedComponents)) {
            throw new Exception\InvalidComponentType('iCalendar objects must at least have a component of type ' . implode(', ', $supportedComponents));
        }

        $options = VObject\Node::PROFILE_CALDAV;
        $prefer = $this->server->getHTTPPrefer();

        if ($prefer['handling'] !== 'strict') {
            $options |= VObject\Node::REPAIR;
        }

        $messages = $vobj->validate($options);

        $highestLevel = 0;
        $warningMessage = null;

                        foreach ($messages as $message) {

            if ($message['level'] > $highestLevel) {
                                $highestLevel = $message['level'];
                $warningMessage = $message['message'];
            }
            switch ($message['level']) {

                case 1 :
                                        $modified = true;
                    break;
                case 2 :
                                        break;
                case 3 :
                                        throw new DAV\Exception\UnsupportedMediaType('Validation error in iCalendar: ' . $message['message']);

            }

        }
        if ($warningMessage) {
            $response->setHeader(
                'X-Sabre-Ew-Gross',
                'iCalendar validation warning: ' . $warningMessage
            );
        }

                                        $subModified = false;

        $this->server->emit(
            'calendarObjectChange',
            [
                $request,
                $response,
                $vobj,
                $parentPath,
                &$subModified,
                $isNew
            ]
        );

        if ($modified || $subModified) {
                        $data = $vobj->serialize();

                        if (!$modified && strcmp($data, $before) !== 0) {
                $modified = true;
            }

        }

                $vobj->destroy();

    }

    
    function getSupportedPrivilegeSet(INode $node, array &$supportedPrivilegeSet) {

        if ($node instanceof ICalendar) {
            $supportedPrivilegeSet['{DAV:}read']['aggregates']['{' . self::NS_CALDAV . '}read-free-busy'] = [
                'abstract'   => false,
                'aggregates' => [],
            ];
        }
    }

    
    function htmlActionsPanel(DAV\INode $node, &$output) {

        if (!$node instanceof CalendarHome)
            return;

        $output .= '<tr><td colspan="2"><form method="post" action="">
            <h3>Create new calendar</h3>
            <input type="hidden" name="sabreAction" value="mkcol" />
            <input type="hidden" name="resourceType" value="{DAV:}collection,{' . self::NS_CALDAV . '}calendar" />
            <label>Name (uri):</label> <input type="text" name="name" /><br />
            <label>Display name:</label> <input type="text" name="{DAV:}displayname" /><br />
            <input type="submit" value="create" />
            </form>
            </td></tr>';

        return false;

    }

    
    function httpAfterGet(RequestInterface $request, ResponseInterface $response) {

        if (strpos($response->getHeader('Content-Type'), 'text/calendar') === false) {
            return;
        }

        $result = HTTP\Util::negotiate(
            $request->getHeader('Accept'),
            ['text/calendar', 'application/calendar+json']
        );

        if ($result !== 'application/calendar+json') {
                        return;
        }

                $vobj = VObject\Reader::read($response->getBody());

        $jsonBody = json_encode($vobj->jsonSerialize());
        $response->setBody($jsonBody);

                $vobj->destroy();

        $response->setHeader('Content-Type', 'application/calendar+json');
        $response->setHeader('Content-Length', strlen($jsonBody));

    }

    
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'Adds support for CalDAV (rfc4791)',
            'link'        => 'http://sabre.io/dav/caldav/',
        ];

    }

}
