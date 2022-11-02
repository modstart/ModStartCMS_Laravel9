<?php

namespace Sabre\CalDAV;

use DateTime;
use DateTimeZone;
use Sabre\DAV;
use Sabre\DAV\Exception\BadRequest;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject;


class ICSExportPlugin extends DAV\ServerPlugin {

    
    protected $server;

    
    function initialize(DAV\Server $server) {

        $this->server = $server;
        $server->on('method:GET', [$this, 'httpGet'], 90);
        $server->on('browserButtonActions', function($path, $node, &$actions) {
            if ($node instanceof ICalendar) {
                $actions .= '<a href="' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . '?export"><span class="oi" data-glyph="calendar"></span></a>';
            }
        });

    }

    
    function httpGet(RequestInterface $request, ResponseInterface $response) {

        $queryParams = $request->getQueryParameters();
        if (!array_key_exists('export', $queryParams)) return;

        $path = $request->getPath();

        $node = $this->server->getProperties($path, [
            '{DAV:}resourcetype',
            '{DAV:}displayname',
            '{http://sabredav.org/ns}sync-token',
            '{DAV:}sync-token',
            '{http://apple.com/ns/ical/}calendar-color',
        ]);

        if (!isset($node['{DAV:}resourcetype']) || !$node['{DAV:}resourcetype']->is('{' . Plugin::NS_CALDAV . '}calendar')) {
            return;
        }
                $this->server->transactionType = 'get-calendar-export';

        $properties = $node;

        $start = null;
        $end = null;
        $expand = false;
        $componentType = false;
        if (isset($queryParams['start'])) {
            if (!ctype_digit($queryParams['start'])) {
                throw new BadRequest('The start= parameter must contain a unix timestamp');
            }
            $start = DateTime::createFromFormat('U', $queryParams['start']);
        }
        if (isset($queryParams['end'])) {
            if (!ctype_digit($queryParams['end'])) {
                throw new BadRequest('The end= parameter must contain a unix timestamp');
            }
            $end = DateTime::createFromFormat('U', $queryParams['end']);
        }
        if (isset($queryParams['expand']) && !!$queryParams['expand']) {
            if (!$start || !$end) {
                throw new BadRequest('If you\'d like to expand recurrences, you must specify both a start= and end= parameter.');
            }
            $expand = true;
            $componentType = 'VEVENT';
        }
        if (isset($queryParams['componentType'])) {
            if (!in_array($queryParams['componentType'], ['VEVENT', 'VTODO', 'VJOURNAL'])) {
                throw new BadRequest('You are not allowed to search for components of type: ' . $queryParams['componentType'] . ' here');
            }
            $componentType = $queryParams['componentType'];
        }

        $format = \Sabre\HTTP\Util::Negotiate(
            $request->getHeader('Accept'),
            [
                'text/calendar',
                'application/calendar+json',
            ]
        );

        if (isset($queryParams['accept'])) {
            if ($queryParams['accept'] === 'application/calendar+json' || $queryParams['accept'] === 'jcal') {
                $format = 'application/calendar+json';
            }
        }
        if (!$format) {
            $format = 'text/calendar';
        }

        $this->generateResponse($path, $start, $end, $expand, $componentType, $format, $properties, $response);

                return false;

    }

    
    protected function generateResponse($path, $start, $end, $expand, $componentType, $format, $properties, ResponseInterface $response) {

        $calDataProp = '{' . Plugin::NS_CALDAV . '}calendar-data';
        $calendarNode = $this->server->tree->getNodeForPath($path);

        $blobs = [];
        if ($start || $end || $componentType) {

                                    $queryResult = $calendarNode->calendarQuery([
                'name'         => 'VCALENDAR',
                'comp-filters' => [
                    [
                        'name'           => $componentType,
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

                                    $queryResult = array_map(
                function($item) use ($path) {
                    return $path . '/' . $item;
                },
                $queryResult
            );
            $nodes = $this->server->getPropertiesForMultiplePaths($queryResult, [$calDataProp]);
            unset($queryResult);

        } else {
            $nodes = $this->server->getPropertiesForPath($path, [$calDataProp], 1);
        }

                foreach ($nodes as $node) {
            if (isset($node[200][$calDataProp])) {
                $blobs[$node['href']] = $node[200][$calDataProp];
            }
        }
        unset($nodes);

        $mergedCalendar = $this->mergeObjects(
            $properties,
            $blobs
        );

        if ($expand) {
            $calendarTimeZone = null;
                                    $tzProp = '{' . Plugin::NS_CALDAV . '}calendar-timezone';
            $tzResult = $this->server->getProperties($path, [$tzProp]);
            if (isset($tzResult[$tzProp])) {
                                                $vtimezoneObj = VObject\Reader::read($tzResult[$tzProp]);
                $calendarTimeZone = $vtimezoneObj->VTIMEZONE->getTimeZone();
                                $vtimezoneObj->destroy();
                unset($vtimezoneObj);
            } else {
                                $calendarTimeZone = new DateTimeZone('UTC');
            }

            $mergedCalendar = $mergedCalendar->expand($start, $end, $calendarTimeZone);
        }

        $filenameExtension = '.ics';

        switch ($format) {
            case 'text/calendar' :
                $mergedCalendar = $mergedCalendar->serialize();
                $filenameExtension = '.ics';
                break;
            case 'application/calendar+json' :
                $mergedCalendar = json_encode($mergedCalendar->jsonSerialize());
                $filenameExtension = '.json';
                break;
        }

        $filename = preg_replace(
            '/[^a-zA-Z0-9-_ ]/um',
            '',
            $calendarNode->getName()
        );
        $filename .= '-' . date('Y-m-d') . $filenameExtension;

        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setHeader('Content-Type', $format);

        $response->setStatus(200);
        $response->setBody($mergedCalendar);

    }

    
    function mergeObjects(array $properties, array $inputObjects) {

        $calendar = new VObject\Component\VCalendar();
        $calendar->VERSION = '2.0';
        if (DAV\Server::$exposeVersion) {
            $calendar->PRODID = '-//SabreDAV//SabreDAV ' . DAV\Version::VERSION . '//EN';
        } else {
            $calendar->PRODID = '-//SabreDAV//SabreDAV//EN';
        }
        if (isset($properties['{DAV:}displayname'])) {
            $calendar->{'X-WR-CALNAME'} = $properties['{DAV:}displayname'];
        }
        if (isset($properties['{http://apple.com/ns/ical/}calendar-color'])) {
            $calendar->{'X-APPLE-CALENDAR-COLOR'} = $properties['{http://apple.com/ns/ical/}calendar-color'];
        }

        $collectedTimezones = [];

        $timezones = [];
        $objects = [];

        foreach ($inputObjects as $href => $inputObject) {

            $nodeComp = VObject\Reader::read($inputObject);

            foreach ($nodeComp->children() as $child) {

                switch ($child->name) {
                    case 'VEVENT' :
                    case 'VTODO' :
                    case 'VJOURNAL' :
                        $objects[] = clone $child;
                        break;

                                        case 'VTIMEZONE' :
                                                if (in_array((string)$child->TZID, $collectedTimezones)) continue;

                        $timezones[] = clone $child;
                        $collectedTimezones[] = $child->TZID;
                        break;

                }

            }
                        $nodeComp->destroy();
            unset($nodeComp);

        }

        foreach ($timezones as $tz) $calendar->add($tz);
        foreach ($objects as $obj) $calendar->add($obj);

        return $calendar;

    }

    
    function getPluginName() {

        return 'ics-export';

    }

    
    function getPluginInfo() {

        return [
            'name'        => $this->getPluginName(),
            'description' => 'Adds the ability to export CalDAV calendars as a single iCalendar file.',
            'link'        => 'http://sabre.io/dav/ics-export-plugin/',
        ];

    }

}
