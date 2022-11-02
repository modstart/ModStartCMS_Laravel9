<?php

namespace Sabre\VObject\Component;

use DateTimeInterface;
use Sabre\VObject;


class VTodo extends VObject\Component
{
    
    public function isInTimeRange(DateTimeInterface $start, DateTimeInterface $end)
    {
        $dtstart = isset($this->DTSTART) ? $this->DTSTART->getDateTime() : null;
        $duration = isset($this->DURATION) ? VObject\DateTimeParser::parseDuration($this->DURATION) : null;
        $due = isset($this->DUE) ? $this->DUE->getDateTime() : null;
        $completed = isset($this->COMPLETED) ? $this->COMPLETED->getDateTime() : null;
        $created = isset($this->CREATED) ? $this->CREATED->getDateTime() : null;

        if ($dtstart) {
            if ($duration) {
                $effectiveEnd = $dtstart->add($duration);

                return $start <= $effectiveEnd && $end > $dtstart;
            } elseif ($due) {
                return
                    ($start < $due || $start <= $dtstart) &&
                    ($end > $dtstart || $end >= $due);
            } else {
                return $start <= $dtstart && $end > $dtstart;
            }
        }
        if ($due) {
            return $start < $due && $end >= $due;
        }
        if ($completed && $created) {
            return
                ($start <= $created || $start <= $completed) &&
                ($end >= $created || $end >= $completed);
        }
        if ($completed) {
            return $start <= $completed && $end >= $completed;
        }
        if ($created) {
            return $end > $created;
        }

        return true;
    }

    
    public function getValidationRules()
    {
        return [
            'UID' => 1,
            'DTSTAMP' => 1,

            'CLASS' => '?',
            'COMPLETED' => '?',
            'CREATED' => '?',
            'DESCRIPTION' => '?',
            'DTSTART' => '?',
            'GEO' => '?',
            'LAST-MODIFIED' => '?',
            'LOCATION' => '?',
            'ORGANIZER' => '?',
            'PERCENT' => '?',
            'PRIORITY' => '?',
            'RECURRENCE-ID' => '?',
            'SEQUENCE' => '?',
            'STATUS' => '?',
            'SUMMARY' => '?',
            'URL' => '?',

            'RRULE' => '?',
            'DUE' => '?',
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

    
    public function validate($options = 0)
    {
        $result = parent::validate($options);
        if (isset($this->DUE) && isset($this->DTSTART)) {
            $due = $this->DUE;
            $dtStart = $this->DTSTART;

            if ($due->getValueType() !== $dtStart->getValueType()) {
                $result[] = [
                    'level' => 3,
                    'message' => 'The value type (DATE or DATE-TIME) must be identical for DUE and DTSTART',
                    'node' => $due,
                ];
            } elseif ($due->getDateTime() < $dtStart->getDateTime()) {
                $result[] = [
                    'level' => 3,
                    'message' => 'DUE must occur after DTSTART',
                    'node' => $due,
                ];
            }
        }

        return $result;
    }

    
    protected function getDefaults()
    {
        return [
            'UID' => 'sabre-vobject-'.VObject\UUIDUtil::getUUID(),
            'DTSTAMP' => date('Ymd\\THis\\Z'),
        ];
    }
}
