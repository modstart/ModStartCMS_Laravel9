<?php

namespace Sabre\CalDAV;

use DateTime;
use Sabre\VObject;


class CalendarQueryValidator {

    
    function validate(VObject\Component\VCalendar $vObject, array $filters) {

                        if ($vObject->name !== $filters['name']) {
            return false;
        }

        return
            $this->validateCompFilters($vObject, $filters['comp-filters']) &&
            $this->validatePropFilters($vObject, $filters['prop-filters']);


    }

    
    protected function validateCompFilters(VObject\Component $parent, array $filters) {

        foreach ($filters as $filter) {

            $isDefined = isset($parent->{$filter['name']});

            if ($filter['is-not-defined']) {

                if ($isDefined) {
                    return false;
                } else {
                    continue;
                }

            }
            if (!$isDefined) {
                return false;
            }

            if ($filter['time-range']) {
                foreach ($parent->{$filter['name']} as $subComponent) {
                    if ($this->validateTimeRange($subComponent, $filter['time-range']['start'], $filter['time-range']['end'])) {
                        continue 2;
                    }
                }
                return false;
            }

            if (!$filter['comp-filters'] && !$filter['prop-filters']) {
                continue;
            }

                                    foreach ($parent->{$filter['name']} as $subComponent) {

                if (
                    $this->validateCompFilters($subComponent, $filter['comp-filters']) &&
                    $this->validatePropFilters($subComponent, $filter['prop-filters'])) {
                                                continue 2;
                }

            }

                                                return false;

        }

                        return true;

    }

    
    protected function validatePropFilters(VObject\Component $parent, array $filters) {

        foreach ($filters as $filter) {

            $isDefined = isset($parent->{$filter['name']});

            if ($filter['is-not-defined']) {

                if ($isDefined) {
                    return false;
                } else {
                    continue;
                }

            }
            if (!$isDefined) {
                return false;
            }

            if ($filter['time-range']) {
                foreach ($parent->{$filter['name']} as $subComponent) {
                    if ($this->validateTimeRange($subComponent, $filter['time-range']['start'], $filter['time-range']['end'])) {
                        continue 2;
                    }
                }
                return false;
            }

            if (!$filter['param-filters'] && !$filter['text-match']) {
                continue;
            }

                                    foreach ($parent->{$filter['name']} as $subComponent) {

                if (
                    $this->validateParamFilters($subComponent, $filter['param-filters']) &&
                    (!$filter['text-match'] || $this->validateTextMatch($subComponent, $filter['text-match']))
                ) {
                                        continue 2;
                }

            }

                                                return false;

        }

                        return true;

    }

    
    protected function validateParamFilters(VObject\Property $parent, array $filters) {

        foreach ($filters as $filter) {

            $isDefined = isset($parent[$filter['name']]);

            if ($filter['is-not-defined']) {

                if ($isDefined) {
                    return false;
                } else {
                    continue;
                }

            }
            if (!$isDefined) {
                return false;
            }

            if (!$filter['text-match']) {
                continue;
            }

                                    foreach ($parent[$filter['name']]->getParts() as $paramPart) {

                if ($this->validateTextMatch($paramPart, $filter['text-match'])) {
                                        continue 2;
                }

            }

                                    return false;

        }

                        return true;

    }

    
    protected function validateTextMatch($check, array $textMatch) {

        if ($check instanceof VObject\Node) {
            $check = $check->getValue();
        }

        $isMatching = \Sabre\DAV\StringUtil::textMatch($check, $textMatch['value'], $textMatch['collation']);

        return ($textMatch['negate-condition'] xor $isMatching);

    }

    
    protected function validateTimeRange(VObject\Node $component, $start, $end) {

        if (is_null($start)) {
            $start = new DateTime('1900-01-01');
        }
        if (is_null($end)) {
            $end = new DateTime('3000-01-01');
        }

        switch ($component->name) {

            case 'VEVENT' :
            case 'VTODO' :
            case 'VJOURNAL' :

                return $component->isInTimeRange($start, $end);

            case 'VALARM' :

                                                                                                                if ($component->parent->name === 'VEVENT' && $component->parent->RRULE) {

                                        $it = new VObject\Recur\EventIterator($component->parent->parent, (string)$component->parent->UID);
                    while ($it->valid()) {
                        $expandedEvent = $it->getEventObject();

                                                                                                $firstAlarm = null;
                        if ($expandedEvent->VALARM !== null) {
                            foreach ($expandedEvent->VALARM as $expandedAlarm) {

                                $effectiveTrigger = $expandedAlarm->getEffectiveTriggerTime();
                                if ($expandedAlarm->isInTimeRange($start, $end)) {
                                    return true;
                                }

                                if ((string)$expandedAlarm->TRIGGER['VALUE'] === 'DATE-TIME') {
                                                                                                                                                                                                                                                        } else {
                                                                                                            if (!$firstAlarm || $effectiveTrigger < $firstAlarm) {
                                        $firstAlarm = $effectiveTrigger;
                                    }
                                }
                            }
                        }
                        if (is_null($firstAlarm)) {
                                                                                                                                                                        return false;
                        }
                        if ($firstAlarm > $end) {
                            return false;
                        }
                        $it->next();
                    }
                    return false;
                } else {
                    return $component->isInTimeRange($start, $end);
                }

            case 'VFREEBUSY' :
                throw new \Sabre\DAV\Exception\NotImplemented('time-range filters are currently not supported on ' . $component->name . ' components');

            case 'COMPLETED' :
            case 'CREATED' :
            case 'DTEND' :
            case 'DTSTAMP' :
            case 'DTSTART' :
            case 'DUE' :
            case 'LAST-MODIFIED' :
                return ($start <= $component->getDateTime() && $end >= $component->getDateTime());



            default :
                throw new \Sabre\DAV\Exception\BadRequest('You cannot create a time-range filter on a ' . $component->name . ' component');

        }

    }

}
