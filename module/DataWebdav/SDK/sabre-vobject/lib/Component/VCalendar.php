<?php

namespace Sabre\VObject\Component;

use DateTimeInterface;
use DateTimeZone;
use Sabre\VObject;
use Sabre\VObject\Component;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\Property;
use Sabre\VObject\Recur\EventIterator;
use Sabre\VObject\Recur\NoInstancesException;


class VCalendar extends VObject\Document
{
    
    public static $defaultName = 'VCALENDAR';

    
    public static $componentMap = [
        'VCALENDAR' => 'Sabre\\VObject\\Component\\VCalendar',
        'VALARM' => 'Sabre\\VObject\\Component\\VAlarm',
        'VEVENT' => 'Sabre\\VObject\\Component\\VEvent',
        'VFREEBUSY' => 'Sabre\\VObject\\Component\\VFreeBusy',
        'VAVAILABILITY' => 'Sabre\\VObject\\Component\\VAvailability',
        'AVAILABLE' => 'Sabre\\VObject\\Component\\Available',
        'VJOURNAL' => 'Sabre\\VObject\\Component\\VJournal',
        'VTIMEZONE' => 'Sabre\\VObject\\Component\\VTimeZone',
        'VTODO' => 'Sabre\\VObject\\Component\\VTodo',
    ];

    
    public static $valueMap = [
        'BINARY' => 'Sabre\\VObject\\Property\\Binary',
        'BOOLEAN' => 'Sabre\\VObject\\Property\\Boolean',
        'CAL-ADDRESS' => 'Sabre\\VObject\\Property\\ICalendar\\CalAddress',
        'DATE' => 'Sabre\\VObject\\Property\\ICalendar\\Date',
        'DATE-TIME' => 'Sabre\\VObject\\Property\\ICalendar\\DateTime',
        'DURATION' => 'Sabre\\VObject\\Property\\ICalendar\\Duration',
        'FLOAT' => 'Sabre\\VObject\\Property\\FloatValue',
        'INTEGER' => 'Sabre\\VObject\\Property\\IntegerValue',
        'PERIOD' => 'Sabre\\VObject\\Property\\ICalendar\\Period',
        'RECUR' => 'Sabre\\VObject\\Property\\ICalendar\\Recur',
        'TEXT' => 'Sabre\\VObject\\Property\\Text',
        'TIME' => 'Sabre\\VObject\\Property\\Time',
        'UNKNOWN' => 'Sabre\\VObject\\Property\\Unknown',         'URI' => 'Sabre\\VObject\\Property\\Uri',
        'UTC-OFFSET' => 'Sabre\\VObject\\Property\\UtcOffset',
    ];

    
    public static $propertyMap = [
                'CALSCALE' => 'Sabre\\VObject\\Property\\FlatText',
        'METHOD' => 'Sabre\\VObject\\Property\\FlatText',
        'PRODID' => 'Sabre\\VObject\\Property\\FlatText',
        'VERSION' => 'Sabre\\VObject\\Property\\FlatText',

                'ATTACH' => 'Sabre\\VObject\\Property\\Uri',
        'CATEGORIES' => 'Sabre\\VObject\\Property\\Text',
        'CLASS' => 'Sabre\\VObject\\Property\\FlatText',
        'COMMENT' => 'Sabre\\VObject\\Property\\FlatText',
        'DESCRIPTION' => 'Sabre\\VObject\\Property\\FlatText',
        'GEO' => 'Sabre\\VObject\\Property\\FloatValue',
        'LOCATION' => 'Sabre\\VObject\\Property\\FlatText',
        'PERCENT-COMPLETE' => 'Sabre\\VObject\\Property\\IntegerValue',
        'PRIORITY' => 'Sabre\\VObject\\Property\\IntegerValue',
        'RESOURCES' => 'Sabre\\VObject\\Property\\Text',
        'STATUS' => 'Sabre\\VObject\\Property\\FlatText',
        'SUMMARY' => 'Sabre\\VObject\\Property\\FlatText',

                'COMPLETED' => 'Sabre\\VObject\\Property\\ICalendar\\DateTime',
        'DTEND' => 'Sabre\\VObject\\Property\\ICalendar\\DateTime',
        'DUE' => 'Sabre\\VObject\\Property\\ICalendar\\DateTime',
        'DTSTART' => 'Sabre\\VObject\\Property\\ICalendar\\DateTime',
        'DURATION' => 'Sabre\\VObject\\Property\\ICalendar\\Duration',
        'FREEBUSY' => 'Sabre\\VObject\\Property\\ICalendar\\Period',
        'TRANSP' => 'Sabre\\VObject\\Property\\FlatText',

                'TZID' => 'Sabre\\VObject\\Property\\FlatText',
        'TZNAME' => 'Sabre\\VObject\\Property\\FlatText',
        'TZOFFSETFROM' => 'Sabre\\VObject\\Property\\UtcOffset',
        'TZOFFSETTO' => 'Sabre\\VObject\\Property\\UtcOffset',
        'TZURL' => 'Sabre\\VObject\\Property\\Uri',

                'ATTENDEE' => 'Sabre\\VObject\\Property\\ICalendar\\CalAddress',
        'CONTACT' => 'Sabre\\VObject\\Property\\FlatText',
        'ORGANIZER' => 'Sabre\\VObject\\Property\\ICalendar\\CalAddress',
        'RECURRENCE-ID' => 'Sabre\\VObject\\Property\\ICalendar\\DateTime',
        'RELATED-TO' => 'Sabre\\VObject\\Property\\FlatText',
        'URL' => 'Sabre\\VObject\\Property\\Uri',
        'UID' => 'Sabre\\VObject\\Property\\FlatText',

                'EXDATE' => 'Sabre\\VObject\\Property\\ICalendar\\DateTime',
        'RDATE' => 'Sabre\\VObject\\Property\\ICalendar\\DateTime',
        'RRULE' => 'Sabre\\VObject\\Property\\ICalendar\\Recur',
        'EXRULE' => 'Sabre\\VObject\\Property\\ICalendar\\Recur', 
                'ACTION' => 'Sabre\\VObject\\Property\\FlatText',
        'REPEAT' => 'Sabre\\VObject\\Property\\IntegerValue',
        'TRIGGER' => 'Sabre\\VObject\\Property\\ICalendar\\Duration',

                'CREATED' => 'Sabre\\VObject\\Property\\ICalendar\\DateTime',
        'DTSTAMP' => 'Sabre\\VObject\\Property\\ICalendar\\DateTime',
        'LAST-MODIFIED' => 'Sabre\\VObject\\Property\\ICalendar\\DateTime',
        'SEQUENCE' => 'Sabre\\VObject\\Property\\IntegerValue',

                'REQUEST-STATUS' => 'Sabre\\VObject\\Property\\Text',

                'ALARM-AGENT' => 'Sabre\\VObject\\Property\\Text',
        'ACKNOWLEDGED' => 'Sabre\\VObject\\Property\\ICalendar\\DateTime',
        'PROXIMITY' => 'Sabre\\VObject\\Property\\Text',
        'DEFAULT-ALARM' => 'Sabre\\VObject\\Property\\Boolean',

                'BUSYTYPE' => 'Sabre\\VObject\\Property\\Text',
    ];

    
    public function getDocumentType()
    {
        return self::ICALENDAR20;
    }

    
    public function getBaseComponents($componentName = null)
    {
        $isBaseComponent = function ($component) {
            if (!$component instanceof VObject\Component) {
                return false;
            }
            if ('VTIMEZONE' === $component->name) {
                return false;
            }
            if (isset($component->{'RECURRENCE-ID'})) {
                return false;
            }

            return true;
        };

        if ($componentName) {
                        return array_filter(
                $this->select($componentName),
                $isBaseComponent
            );
        }

        $components = [];
        foreach ($this->children as $childGroup) {
            foreach ($childGroup as $child) {
                if (!$child instanceof Component) {
                                                            continue 2;
                }
                if ($isBaseComponent($child)) {
                    $components[] = $child;
                }
            }
        }

        return $components;
    }

    
    public function getBaseComponent($componentName = null)
    {
        $isBaseComponent = function ($component) {
            if (!$component instanceof VObject\Component) {
                return false;
            }
            if ('VTIMEZONE' === $component->name) {
                return false;
            }
            if (isset($component->{'RECURRENCE-ID'})) {
                return false;
            }

            return true;
        };

        if ($componentName) {
            foreach ($this->select($componentName) as $child) {
                if ($isBaseComponent($child)) {
                    return $child;
                }
            }

            return null;
        }

                foreach ($this->children as $childGroup) {
            foreach ($childGroup as $child) {
                if ($isBaseComponent($child)) {
                    return $child;
                }
            }
        }

        return null;
    }

    
    public function expand(DateTimeInterface $start, DateTimeInterface $end, DateTimeZone $timeZone = null)
    {
        $newChildren = [];
        $recurringEvents = [];

        if (!$timeZone) {
            $timeZone = new DateTimeZone('UTC');
        }

        $stripTimezones = function (Component $component) use ($timeZone, &$stripTimezones) {
            foreach ($component->children() as $componentChild) {
                if ($componentChild instanceof Property\ICalendar\DateTime && $componentChild->hasTime()) {
                    $dt = $componentChild->getDateTimes($timeZone);
                                                                                $dt[0] = $dt[0]->setTimeZone(new DateTimeZone('UTC'));
                    $componentChild->setDateTimes($dt);
                } elseif ($componentChild instanceof Component) {
                    $stripTimezones($componentChild);
                }
            }

            return $component;
        };

        foreach ($this->children() as $child) {
            if ($child instanceof Property && 'PRODID' !== $child->name) {
                                                $newChildren[] = clone $child;
            } elseif ($child instanceof Component && 'VTIMEZONE' !== $child->name) {
                                                if ('VEVENT' === $child->name && (isset($child->{'RECURRENCE-ID'}) || isset($child->RRULE) || isset($child->RDATE))) {
                                        $uid = (string) $child->UID;
                    if (!$uid) {
                        throw new InvalidDataException('Every VEVENT object must have a UID property');
                    }
                    if (isset($recurringEvents[$uid])) {
                        $recurringEvents[$uid][] = clone $child;
                    } else {
                        $recurringEvents[$uid] = [clone $child];
                    }
                } elseif ('VEVENT' === $child->name && $child->isInTimeRange($start, $end)) {
                    $newChildren[] = $stripTimezones(clone $child);
                }
            }
        }

        foreach ($recurringEvents as $events) {
            try {
                $it = new EventIterator($events, null, $timeZone);
            } catch (NoInstancesException $e) {
                                                                continue;
            }
            $it->fastForward($start);

            while ($it->valid() && $it->getDTStart() < $end) {
                if ($it->getDTEnd() > $start) {
                    $newChildren[] = $stripTimezones($it->getEventObject());
                }
                $it->next();
            }
        }

        return new self($newChildren);
    }

    
    protected function getDefaults()
    {
        return [
            'VERSION' => '2.0',
            'PRODID' => '-//Sabre//Sabre VObject '.VObject\Version::VERSION.'//EN',
            'CALSCALE' => 'GREGORIAN',
        ];
    }

    
    public function getValidationRules()
    {
        return [
            'PRODID' => 1,
            'VERSION' => 1,

            'CALSCALE' => '?',
            'METHOD' => '?',
        ];
    }

    
    public function validate($options = 0)
    {
        $warnings = parent::validate($options);

        if ($ver = $this->VERSION) {
            if ('2.0' !== (string) $ver) {
                $warnings[] = [
                    'level' => 3,
                    'message' => 'Only iCalendar version 2.0 as defined in rfc5545 is supported.',
                    'node' => $this,
                ];
            }
        }

        $uidList = [];
        $componentsFound = 0;
        $componentTypes = [];

        foreach ($this->children() as $child) {
            if ($child instanceof Component) {
                ++$componentsFound;

                if (!in_array($child->name, ['VEVENT', 'VTODO', 'VJOURNAL'])) {
                    continue;
                }
                $componentTypes[] = $child->name;

                $uid = (string) $child->UID;
                $isMaster = isset($child->{'RECURRENCE-ID'}) ? 0 : 1;
                if (isset($uidList[$uid])) {
                    ++$uidList[$uid]['count'];
                    if ($isMaster && $uidList[$uid]['hasMaster']) {
                        $warnings[] = [
                            'level' => 3,
                            'message' => 'More than one master object was found for the object with UID '.$uid,
                            'node' => $this,
                        ];
                    }
                    $uidList[$uid]['hasMaster'] += $isMaster;
                } else {
                    $uidList[$uid] = [
                        'count' => 1,
                        'hasMaster' => $isMaster,
                    ];
                }
            }
        }

        if (0 === $componentsFound) {
            $warnings[] = [
                'level' => 3,
                'message' => 'An iCalendar object must have at least 1 component.',
                'node' => $this,
            ];
        }

        if ($options & self::PROFILE_CALDAV) {
            if (count($uidList) > 1) {
                $warnings[] = [
                    'level' => 3,
                    'message' => 'A calendar object on a CalDAV server may only have components with the same UID.',
                    'node' => $this,
                ];
            }
            if (0 === count($componentTypes)) {
                $warnings[] = [
                    'level' => 3,
                    'message' => 'A calendar object on a CalDAV server must have at least 1 component (VTODO, VEVENT, VJOURNAL).',
                    'node' => $this,
                ];
            }
            if (count(array_unique($componentTypes)) > 1) {
                $warnings[] = [
                    'level' => 3,
                    'message' => 'A calendar object on a CalDAV server may only have 1 type of component (VEVENT, VTODO or VJOURNAL).',
                    'node' => $this,
                ];
            }

            if (isset($this->METHOD)) {
                $warnings[] = [
                    'level' => 3,
                    'message' => 'A calendar object on a CalDAV server MUST NOT have a METHOD property.',
                    'node' => $this,
                ];
            }
        }

        return $warnings;
    }

    
    public function getByUID($uid)
    {
        return array_filter($this->getComponents(), function ($item) use ($uid) {
            if (!$itemUid = $item->select('UID')) {
                return false;
            }
            $itemUid = current($itemUid)->getValue();

            return $uid === $itemUid;
        });
    }
}
