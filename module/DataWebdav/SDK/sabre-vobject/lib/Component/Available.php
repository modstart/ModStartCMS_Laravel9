<?php

namespace Sabre\VObject\Component;

use Sabre\VObject;


class Available extends VObject\Component
{
    
    public function getEffectiveStartEnd()
    {
        $effectiveStart = $this->DTSTART->getDateTime();
        if (isset($this->DTEND)) {
            $effectiveEnd = $this->DTEND->getDateTime();
        } else {
            $effectiveEnd = $effectiveStart->add(VObject\DateTimeParser::parseDuration($this->DURATION));
        }

        return [$effectiveStart, $effectiveEnd];
    }

    
    public function getValidationRules()
    {
        return [
            'UID' => 1,
            'DTSTART' => 1,
            'DTSTAMP' => 1,

            'DTEND' => '?',
            'DURATION' => '?',

            'CREATED' => '?',
            'DESCRIPTION' => '?',
            'LAST-MODIFIED' => '?',
            'RECURRENCE-ID' => '?',
            'RRULE' => '?',
            'SUMMARY' => '?',

            'CATEGORIES' => '*',
            'COMMENT' => '*',
            'CONTACT' => '*',
            'EXDATE' => '*',
            'RDATE' => '*',

            'AVAILABLE' => '*',
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
