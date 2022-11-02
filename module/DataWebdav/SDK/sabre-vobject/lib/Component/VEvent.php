<?php

namespace Sabre\VObject\Component;

use DateTimeInterface;
use Sabre\VObject;
use Sabre\VObject\Recur\EventIterator;
use Sabre\VObject\Recur\NoInstancesException;


class VEvent extends VObject\Component
{
    
    public function isInTimeRange(DateTimeInterface $start, DateTimeInterface $end)
    {
        if ($this->RRULE) {
            try {
                $it = new EventIterator($this, null, $start->getTimezone());
            } catch (NoInstancesException $e) {
                                                return false;
            }

            $it->fastForward($start);

                                                                                    return $it->getDTStart() < $end && $it->getDTEnd() > $start;
        }

        $effectiveStart = $this->DTSTART->getDateTime($start->getTimezone());
        if (isset($this->DTEND)) {
                                                                                    $effectiveEnd = $this->DTEND->getDateTime($end->getTimezone());
        } elseif (isset($this->DURATION)) {
            $effectiveEnd = $effectiveStart->add(VObject\DateTimeParser::parseDuration($this->DURATION));
        } elseif (!$this->DTSTART->hasTime()) {
            $effectiveEnd = $effectiveStart->modify('+1 day');
        } else {
            $effectiveEnd = $effectiveStart;
        }

        return
            ($start < $effectiveEnd) && ($end > $effectiveStart)
        ;
    }

    
    protected function getDefaults()
    {
        return [
            'UID' => 'sabre-vobject-'.VObject\UUIDUtil::getUUID(),
            'DTSTAMP' => gmdate('Ymd\\THis\\Z'),
        ];
    }

    
    public function getValidationRules()
    {
        $hasMethod = isset($this->parent->METHOD);

        return [
            'UID' => 1,
            'DTSTAMP' => 1,
            'DTSTART' => $hasMethod ? '?' : '1',
            'CLASS' => '?',
            'CREATED' => '?',
            'DESCRIPTION' => '?',
            'GEO' => '?',
            'LAST-MODIFIED' => '?',
            'LOCATION' => '?',
            'ORGANIZER' => '?',
            'PRIORITY' => '?',
            'SEQUENCE' => '?',
            'STATUS' => '?',
            'SUMMARY' => '?',
            'TRANSP' => '?',
            'URL' => '?',
            'RECURRENCE-ID' => '?',
            'RRULE' => '?',
            'DTEND' => '?',
            'DURATION' => '?',

            'ATTACH' => '*',
            'ATTENDEE' => '*',
            'CATEGORIES' => '*',
            'COMMENT' => '*',
            'CONTACT' => '*',
            'EXDATE' => '*',
            'REQUEST-STATUS' => '*',
            'RELATED-TO' => '*',
            'RESOURCES' => '*',
            'RDATE' => '*',
        ];
    }
}
