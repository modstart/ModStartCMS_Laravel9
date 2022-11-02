<?php

namespace Sabre\VObject\Component;

use DateTimeInterface;
use Sabre\VObject;


class VJournal extends VObject\Component
{
    
    public function isInTimeRange(DateTimeInterface $start, DateTimeInterface $end)
    {
        $dtstart = isset($this->DTSTART) ? $this->DTSTART->getDateTime() : null;
        if ($dtstart) {
            $effectiveEnd = $dtstart;
            if (!$this->DTSTART->hasTime()) {
                $effectiveEnd = $effectiveEnd->modify('+1 day');
            }

            return $start <= $effectiveEnd && $end > $dtstart;
        }

        return false;
    }

    
    public function getValidationRules()
    {
        return [
            'UID' => 1,
            'DTSTAMP' => 1,

            'CLASS' => '?',
            'CREATED' => '?',
            'DTSTART' => '?',
            'LAST-MODIFIED' => '?',
            'ORGANIZER' => '?',
            'RECURRENCE-ID' => '?',
            'SEQUENCE' => '?',
            'STATUS' => '?',
            'SUMMARY' => '?',
            'URL' => '?',

            'RRULE' => '?',

            'ATTACH' => '*',
            'ATTENDEE' => '*',
            'CATEGORIES' => '*',
            'COMMENT' => '*',
            'CONTACT' => '*',
            'DESCRIPTION' => '*',
            'EXDATE' => '*',
            'RELATED-TO' => '*',
            'RDATE' => '*',
        ];
    }

    
    protected function getDefaults()
    {
        return [
            'UID' => 'sabre-vobject-'.VObject\UUIDUtil::getUUID(),
            'DTSTAMP' => gmdate('Ymd\\THis\\Z'),
        ];
    }
}
