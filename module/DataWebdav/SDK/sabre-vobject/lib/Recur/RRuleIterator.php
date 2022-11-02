<?php

namespace Sabre\VObject\Recur;

use DateTimeImmutable;
use DateTimeInterface;
use Iterator;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\Property;


class RRuleIterator implements Iterator
{
    
    public function __construct($rrule, DateTimeInterface $start)
    {
        $this->startDate = $start;
        $this->parseRRule($rrule);
        $this->currentDate = clone $this->startDate;
    }

    

    public function current()
    {
        if (!$this->valid()) {
            return;
        }

        return clone $this->currentDate;
    }

    
    public function key()
    {
        return $this->counter;
    }

    
    public function valid()
    {
        if (null === $this->currentDate) {
            return false;
        }
        if (!is_null($this->count)) {
            return $this->counter < $this->count;
        }

        return is_null($this->until) || $this->currentDate <= $this->until;
    }

    
    public function rewind()
    {
        $this->currentDate = clone $this->startDate;
        $this->counter = 0;
    }

    
    public function next()
    {
                        switch ($this->frequency) {
            case 'hourly':
                $this->nextHourly();
                break;

            case 'daily':
                $this->nextDaily();
                break;

            case 'weekly':
                $this->nextWeekly();
                break;

            case 'monthly':
                $this->nextMonthly();
                break;

            case 'yearly':
                $this->nextYearly();
                break;
        }
        ++$this->counter;
    }

    

    
    public function isInfinite()
    {
        return !$this->count && !$this->until;
    }

    
    public function fastForward(DateTimeInterface $dt)
    {
        while ($this->valid() && $this->currentDate < $dt) {
            $this->next();
        }
    }

    
    protected $startDate;

    
    protected $currentDate;

    
    protected $frequency;

    
    protected $count;

    
    protected $interval = 1;

    
    protected $until;

    
    protected $bySecond;

    
    protected $byMinute;

    
    protected $byHour;

    
    protected $counter = 0;

    
    protected $byDay;

    
    protected $byMonthDay;

    
    protected $byYearDay;

    
    protected $byWeekNo;

    
    protected $byMonth;

    
    protected $bySetPos;

    
    protected $weekStart = 'MO';

    

    
    protected function nextHourly()
    {
        $this->currentDate = $this->currentDate->modify('+'.$this->interval.' hours');
    }

    
    protected function nextDaily()
    {
        if (!$this->byHour && !$this->byDay) {
            $this->currentDate = $this->currentDate->modify('+'.$this->interval.' days');

            return;
        }

        if (!empty($this->byHour)) {
            $recurrenceHours = $this->getHours();
        }

        if (!empty($this->byDay)) {
            $recurrenceDays = $this->getDays();
        }

        if (!empty($this->byMonth)) {
            $recurrenceMonths = $this->getMonths();
        }

        do {
            if ($this->byHour) {
                if ('23' == $this->currentDate->format('G')) {
                                        $this->currentDate = $this->currentDate->modify('+'.($this->interval - 1).' days');
                }

                $this->currentDate = $this->currentDate->modify('+1 hours');
            } else {
                $this->currentDate = $this->currentDate->modify('+'.$this->interval.' days');
            }

                        $currentMonth = $this->currentDate->format('n');

                        $currentDay = $this->currentDate->format('w');

                        $currentHour = $this->currentDate->format('G');
        } while (
            ($this->byDay && !in_array($currentDay, $recurrenceDays)) ||
            ($this->byHour && !in_array($currentHour, $recurrenceHours)) ||
            ($this->byMonth && !in_array($currentMonth, $recurrenceMonths))
        );
    }

    
    protected function nextWeekly()
    {
        if (!$this->byHour && !$this->byDay) {
            $this->currentDate = $this->currentDate->modify('+'.$this->interval.' weeks');

            return;
        }

        if ($this->byHour) {
            $recurrenceHours = $this->getHours();
        }

        if ($this->byDay) {
            $recurrenceDays = $this->getDays();
        }

                $firstDay = $this->dayMap[$this->weekStart];

        do {
            if ($this->byHour) {
                $this->currentDate = $this->currentDate->modify('+1 hours');
            } else {
                $this->currentDate = $this->currentDate->modify('+1 days');
            }

                        $currentDay = (int) $this->currentDate->format('w');

                        $currentHour = (int) $this->currentDate->format('G');

                        if ($currentDay === $firstDay && (!$this->byHour || '0' == $currentHour)) {
                $this->currentDate = $this->currentDate->modify('+'.($this->interval - 1).' weeks');

                                                if ($this->currentDate->format('w') != $firstDay) {
                    $this->currentDate = $this->currentDate->modify('last '.$this->dayNames[$this->dayMap[$this->weekStart]]);
                }
            }

                    } while (($this->byDay && !in_array($currentDay, $recurrenceDays)) || ($this->byHour && !in_array($currentHour, $recurrenceHours)));
    }

    
    protected function nextMonthly()
    {
        $currentDayOfMonth = $this->currentDate->format('j');
        if (!$this->byMonthDay && !$this->byDay) {
                                                if ($currentDayOfMonth < 29) {
                $this->currentDate = $this->currentDate->modify('+'.$this->interval.' months');
            } else {
                $increase = 0;
                do {
                    ++$increase;
                    $tempDate = clone $this->currentDate;
                    $tempDate = $tempDate->modify('+ '.($this->interval * $increase).' months');
                } while ($tempDate->format('j') != $currentDayOfMonth);
                $this->currentDate = $tempDate;
            }

            return;
        }

        while (true) {
            $occurrences = $this->getMonthlyOccurrences();

            foreach ($occurrences as $occurrence) {
                                                if ($occurrence > $currentDayOfMonth) {
                    break 2;
                }
            }

                                                                                                $this->currentDate = new DateTimeImmutable($this->currentDate->format('Y-m-1 H:i:s'), $this->currentDate->getTimezone());
                        $this->currentDate = $this->currentDate->modify('+ '.$this->interval.' months');

                                    $currentDayOfMonth = 0;

                                    if ($this->currentDate->getTimestamp() > 253402300799) {
                $this->currentDate = null;

                return;
            }
        }

        $this->currentDate = $this->currentDate->setDate(
            (int) $this->currentDate->format('Y'),
            (int) $this->currentDate->format('n'),
            (int) $occurrence
        );
    }

    
    protected function nextYearly()
    {
        $currentMonth = $this->currentDate->format('n');
        $currentYear = $this->currentDate->format('Y');
        $currentDayOfMonth = $this->currentDate->format('j');

                if (empty($this->byMonth)) {
                        if (2 == $currentMonth && 29 == $currentDayOfMonth) {
                $counter = 0;
                do {
                    ++$counter;
                                                                                                                                                                                    $nextDate = clone $this->currentDate;
                    $nextDate = $nextDate->modify('+ '.($this->interval * $counter).' years');
                } while (2 != $nextDate->format('n'));

                $this->currentDate = $nextDate;

                return;
            }

            if (null !== $this->byWeekNo) {                 $dayOffsets = [];
                if ($this->byDay) {
                    foreach ($this->byDay as $byDay) {
                        $dayOffsets[] = $this->dayMap[$byDay];
                    }
                } else {                       $dayOffsets[] = 1;
                }

                $currentYear = $this->currentDate->format('Y');

                while (true) {
                    $checkDates = [];

                                        foreach ($this->byWeekNo as $byWeekNo) {
                        foreach ($dayOffsets as $dayOffset) {
                            $date = clone $this->currentDate;
                            $date->setISODate($currentYear, $byWeekNo, $dayOffset);

                            if ($date > $this->currentDate) {
                                $checkDates[] = $date;
                            }
                        }
                    }

                    if (count($checkDates) > 0) {
                        $this->currentDate = min($checkDates);

                        return;
                    }

                                        $currentYear += $this->interval;
                }
            }

            if (null !== $this->byYearDay) {                 $dayOffsets = [];
                if ($this->byDay) {
                    foreach ($this->byDay as $byDay) {
                        $dayOffsets[] = $this->dayMap[$byDay];
                    }
                } else {                       $dayOffsets = [1, 2, 3, 4, 5, 6, 7];
                }

                $currentYear = $this->currentDate->format('Y');

                while (true) {
                    $checkDates = [];

                                        foreach ($this->byYearDay as $byYearDay) {
                        $date = clone $this->currentDate;
                        $date = $date->setDate($currentYear, 1, 1);
                        if ($byYearDay > 0) {
                            $date = $date->add(new \DateInterval('P'.$byYearDay.'D'));
                        } else {
                            $date = $date->sub(new \DateInterval('P'.abs($byYearDay).'D'));
                        }

                        if ($date > $this->currentDate && in_array($date->format('N'), $dayOffsets)) {
                            $checkDates[] = $date;
                        }
                    }

                    if (count($checkDates) > 0) {
                        $this->currentDate = min($checkDates);

                        return;
                    }

                                        $currentYear += $this->interval;
                }
            }

                        $this->currentDate = $this->currentDate->modify('+'.$this->interval.' years');

            return;
        }

        $currentMonth = $this->currentDate->format('n');
        $currentYear = $this->currentDate->format('Y');
        $currentDayOfMonth = $this->currentDate->format('j');

        $advancedToNewMonth = false;

                        if ($this->byDay || $this->byMonthDay) {
            while (true) {
                $occurrences = $this->getMonthlyOccurrences();

                foreach ($occurrences as $occurrence) {
                                                                                                    if ($occurrence > $currentDayOfMonth || $advancedToNewMonth) {
                        break 2;
                    }
                }

                                                $currentDayOfMonth = 1;
                $advancedToNewMonth = true;
                do {
                    ++$currentMonth;
                    if ($currentMonth > 12) {
                        $currentYear += $this->interval;
                        $currentMonth = 1;
                    }
                } while (!in_array($currentMonth, $this->byMonth));

                $this->currentDate = $this->currentDate->setDate(
                    (int) $currentYear,
                    (int) $currentMonth,
                    (int) $currentDayOfMonth
                );
            }

                        $this->currentDate = $this->currentDate->setDate(
                (int) $currentYear,
                (int) $currentMonth,
                (int) $occurrence
            );

            return;
        } else {
                                    do {
                ++$currentMonth;
                if ($currentMonth > 12) {
                    $currentYear += $this->interval;
                    $currentMonth = 1;
                }
            } while (!in_array($currentMonth, $this->byMonth));
            $this->currentDate = $this->currentDate->setDate(
                (int) $currentYear,
                (int) $currentMonth,
                (int) $currentDayOfMonth
            );

            return;
        }
    }

    

    
    protected function parseRRule($rrule)
    {
        if (is_string($rrule)) {
            $rrule = Property\ICalendar\Recur::stringToArray($rrule);
        }

        foreach ($rrule as $key => $value) {
            $key = strtoupper($key);
            switch ($key) {
                case 'FREQ':
                    $value = strtolower($value);
                    if (!in_array(
                        $value,
                        ['secondly', 'minutely', 'hourly', 'daily', 'weekly', 'monthly', 'yearly']
                    )) {
                        throw new InvalidDataException('Unknown value for FREQ='.strtoupper($value));
                    }
                    $this->frequency = $value;
                    break;

                case 'UNTIL':
                    $this->until = DateTimeParser::parse($value, $this->startDate->getTimezone());

                                                                                                                                                                                    if ($this->until < $this->startDate) {
                        $this->until = $this->startDate;
                    }
                    break;

                case 'INTERVAL':

                case 'COUNT':
                    $val = (int) $value;
                    if ($val < 1) {
                        throw new InvalidDataException(strtoupper($key).' in RRULE must be a positive integer!');
                    }
                    $key = strtolower($key);
                    $this->$key = $val;
                    break;

                case 'BYSECOND':
                    $this->bySecond = (array) $value;
                    break;

                case 'BYMINUTE':
                    $this->byMinute = (array) $value;
                    break;

                case 'BYHOUR':
                    $this->byHour = (array) $value;
                    break;

                case 'BYDAY':
                    $value = (array) $value;
                    foreach ($value as $part) {
                        if (!preg_match('#^  (-|\+)? ([1-5])? (MO|TU|WE|TH|FR|SA|SU) $# xi', $part)) {
                            throw new InvalidDataException('Invalid part in BYDAY clause: '.$part);
                        }
                    }
                    $this->byDay = $value;
                    break;

                case 'BYMONTHDAY':
                    $this->byMonthDay = (array) $value;
                    break;

                case 'BYYEARDAY':
                    $this->byYearDay = (array) $value;
                    foreach ($this->byYearDay as $byYearDay) {
                        if (!is_numeric($byYearDay) || (int) $byYearDay < -366 || 0 == (int) $byYearDay || (int) $byYearDay > 366) {
                            throw new InvalidDataException('BYYEARDAY in RRULE must have value(s) from 1 to 366, or -366 to -1!');
                        }
                    }
                    break;

                case 'BYWEEKNO':
                    $this->byWeekNo = (array) $value;
                    foreach ($this->byWeekNo as $byWeekNo) {
                        if (!is_numeric($byWeekNo) || (int) $byWeekNo < -53 || 0 == (int) $byWeekNo || (int) $byWeekNo > 53) {
                            throw new InvalidDataException('BYWEEKNO in RRULE must have value(s) from 1 to 53, or -53 to -1!');
                        }
                    }
                    break;

                case 'BYMONTH':
                    $this->byMonth = (array) $value;
                    foreach ($this->byMonth as $byMonth) {
                        if (!is_numeric($byMonth) || (int) $byMonth < 1 || (int) $byMonth > 12) {
                            throw new InvalidDataException('BYMONTH in RRULE must have value(s) between 1 and 12!');
                        }
                    }
                    break;

                case 'BYSETPOS':
                    $this->bySetPos = (array) $value;
                    break;

                case 'WKST':
                    $this->weekStart = strtoupper($value);
                    break;

                default:
                    throw new InvalidDataException('Not supported: '.strtoupper($key));
            }
        }
    }

    
    protected $dayNames = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    
    protected function getMonthlyOccurrences()
    {
        $startDate = clone $this->currentDate;

        $byDayResults = [];

                        if ($this->byDay) {
            foreach ($this->byDay as $day) {
                $dayName = $this->dayNames[$this->dayMap[substr($day, -2)]];

                                                $dayHits = [];

                                $checkDate = new \DateTime($startDate->format('Y-m-1'));
                                if ($checkDate->format('l') !== $dayName) {
                    $checkDate = $checkDate->modify($dayName);
                }

                do {
                    $dayHits[] = $checkDate->format('j');
                    $checkDate = $checkDate->modify('next '.$dayName);
                } while ($checkDate->format('n') === $startDate->format('n'));

                                                                if (strlen($day) > 2) {
                    $offset = (int) substr($day, 0, -2);

                    if ($offset > 0) {
                                                                        if (isset($dayHits[$offset - 1])) {
                            $byDayResults[] = $dayHits[$offset - 1];
                        }
                    } else {
                                                                        if (isset($dayHits[count($dayHits) + $offset])) {
                            $byDayResults[] = $dayHits[count($dayHits) + $offset];
                        }
                    }
                } else {
                                                            $byDayResults = array_merge($byDayResults, $dayHits);
                }
            }
        }

        $byMonthDayResults = [];
        if ($this->byMonthDay) {
            foreach ($this->byMonthDay as $monthDay) {
                                if ($monthDay > $startDate->format('t') ||
                $monthDay < 0 - $startDate->format('t')) {
                    continue;
                }
                if ($monthDay > 0) {
                    $byMonthDayResults[] = $monthDay;
                } else {
                                        $byMonthDayResults[] = $startDate->format('t') + 1 + $monthDay;
                }
            }
        }

                                if ($this->byMonthDay && $this->byDay) {
            $result = array_intersect($byMonthDayResults, $byDayResults);
        } elseif ($this->byMonthDay) {
            $result = $byMonthDayResults;
        } else {
            $result = $byDayResults;
        }
        $result = array_unique($result);
        sort($result, SORT_NUMERIC);

                        if (!$this->bySetPos) {
            return $result;
        }

        $filteredResult = [];
        foreach ($this->bySetPos as $setPos) {
            if ($setPos < 0) {
                $setPos = count($result) + ($setPos + 1);
            }
            if (isset($result[$setPos - 1])) {
                $filteredResult[] = $result[$setPos - 1];
            }
        }

        sort($filteredResult, SORT_NUMERIC);

        return $filteredResult;
    }

    
    protected $dayMap = [
        'SU' => 0,
        'MO' => 1,
        'TU' => 2,
        'WE' => 3,
        'TH' => 4,
        'FR' => 5,
        'SA' => 6,
    ];

    protected function getHours()
    {
        $recurrenceHours = [];
        foreach ($this->byHour as $byHour) {
            $recurrenceHours[] = $byHour;
        }

        return $recurrenceHours;
    }

    protected function getDays()
    {
        $recurrenceDays = [];
        foreach ($this->byDay as $byDay) {
                                                $recurrenceDays[] = $this->dayMap[substr($byDay, -2)];
        }

        return $recurrenceDays;
    }

    protected function getMonths()
    {
        $recurrenceMonths = [];
        foreach ($this->byMonth as $byMonth) {
            $recurrenceMonths[] = $byMonth;
        }

        return $recurrenceMonths;
    }
}
