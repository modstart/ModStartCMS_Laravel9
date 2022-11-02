<?php

namespace Sabre\VObject\Component;

use DateTimeInterface;
use Sabre\VObject;


class VAvailability extends VObject\Component
{
    
    public function isInTimeRange(DateTimeInterface $start, DateTimeInterface $end)
    {
        list($effectiveStart, $effectiveEnd) = $this->getEffectiveStartEnd();

        return
            (is_null($effectiveStart) || $start < $effectiveEnd) &&
            (is_null($effectiveEnd) || $end > $effectiveStart)
        ;
    }

    
    public function getEffectiveStartEnd()
    {
        $effectiveStart = null;
        $effectiveEnd = null;

        if (isset($this->DTSTART)) {
            $effectiveStart = $this->DTSTART->getDateTime();
        }
        if (isset($this->DTEND)) {
            $effectiveEnd = $this->DTEND->getDateTime();
        } elseif ($effectiveStart && isset($this->DURATION)) {
            $effectiveEnd = $effectiveStart->add(VObject\DateTimeParser::parseDuration($this->DURATION));
        }

        return [$effectiveStart, $effectiveEnd];
    }

    
    public function getValidationRules()
    {
        return [
            'UID' => 1,
            'DTSTAMP' => 1,

            'BUSYTYPE' => '?',
            'CLASS' => '?',
            'CREATED' => '?',
            'DESCRIPTION' => '?',
            'DTSTART' => '?',
            'LAST-MODIFIED' => '?',
            'ORGANIZER' => '?',
            'PRIORITY' => '?',
            'SEQUENCE' => '?',
            'SUMMARY' => '?',
            'URL' => '?',
            'DTEND' => '?',
            'DURATION' => '?',

            'CATEGORIES' => '*',
            'COMMENT' => '*',
            'CONTACT' => '*',
        ];
    }

    
    public function validate($options = 0)
    {
        $result = parent::validate($options);

        if (isset($this->DTEND) && isset($this->DURATION)) {
            $result[] = [
                'level' => 3,
                'message' => 'DTEND and DURATION cannot both be present',
                'node' => $this,
            ];
        }

        return $result;
    }
}
