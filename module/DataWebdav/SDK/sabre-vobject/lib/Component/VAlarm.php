<?php

namespace Sabre\VObject\Component;

use DateTimeImmutable;
use DateTimeInterface;
use Sabre\VObject;
use Sabre\VObject\InvalidDataException;


class VAlarm extends VObject\Component
{
    
    public function getEffectiveTriggerTime()
    {
        $trigger = $this->TRIGGER;
        if (!isset($trigger['VALUE']) || 'DURATION' === strtoupper($trigger['VALUE'])) {
            $triggerDuration = VObject\DateTimeParser::parseDuration($this->TRIGGER);
            $related = (isset($trigger['RELATED']) && 'END' == strtoupper($trigger['RELATED'])) ? 'END' : 'START';

            $parentComponent = $this->parent;
            if ('START' === $related) {
                if ('VTODO' === $parentComponent->name) {
                    $propName = 'DUE';
                } else {
                    $propName = 'DTSTART';
                }

                $effectiveTrigger = $parentComponent->$propName->getDateTime();
                $effectiveTrigger = $effectiveTrigger->add($triggerDuration);
            } else {
                if ('VTODO' === $parentComponent->name) {
                    $endProp = 'DUE';
                } elseif ('VEVENT' === $parentComponent->name) {
                    $endProp = 'DTEND';
                } else {
                    throw new InvalidDataException('time-range filters on VALARM components are only supported when they are a child of VTODO or VEVENT');
                }

                if (isset($parentComponent->$endProp)) {
                    $effectiveTrigger = $parentComponent->$endProp->getDateTime();
                    $effectiveTrigger = $effectiveTrigger->add($triggerDuration);
                } elseif (isset($parentComponent->DURATION)) {
                    $effectiveTrigger = $parentComponent->DTSTART->getDateTime();
                    $duration = VObject\DateTimeParser::parseDuration($parentComponent->DURATION);
                    $effectiveTrigger = $effectiveTrigger->add($duration);
                    $effectiveTrigger = $effectiveTrigger->add($triggerDuration);
                } else {
                    $effectiveTrigger = $parentComponent->DTSTART->getDateTime();
                    $effectiveTrigger = $effectiveTrigger->add($triggerDuration);
                }
            }
        } else {
            $effectiveTrigger = $trigger->getDateTime();
        }

        return $effectiveTrigger;
    }

    
    public function isInTimeRange(DateTimeInterface $start, DateTimeInterface $end)
    {
        $effectiveTrigger = $this->getEffectiveTriggerTime();

        if (isset($this->DURATION)) {
            $duration = VObject\DateTimeParser::parseDuration($this->DURATION);
            $repeat = (string) $this->REPEAT;
            if (!$repeat) {
                $repeat = 1;
            }

            $period = new \DatePeriod($effectiveTrigger, $duration, (int) $repeat);

            foreach ($period as $occurrence) {
                if ($start <= $occurrence && $end > $occurrence) {
                    return true;
                }
            }

            return false;
        } else {
            return $start <= $effectiveTrigger && $end > $effectiveTrigger;
        }
    }

    
    public function getValidationRules()
    {
        return [
            'ACTION' => 1,
            'TRIGGER' => 1,

            'DURATION' => '?',
            'REPEAT' => '?',

            'ATTACH' => '?',
        ];
    }
}
