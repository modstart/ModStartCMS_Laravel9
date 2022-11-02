<?php

namespace Sabre\VObject;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Recur\EventIterator;
use Sabre\VObject\Recur\NoInstancesException;


class FreeBusyGenerator
{
    
    protected $objects = [];

    
    protected $start;

    
    protected $end;

    
    protected $baseObject;

    
    protected $timeZone;

    
    protected $vavailability;

    
    public function __construct(DateTimeInterface $start = null, DateTimeInterface $end = null, $objects = null, DateTimeZone $timeZone = null)
    {
        $this->setTimeRange($start, $end);

        if ($objects) {
            $this->setObjects($objects);
        }
        if (is_null($timeZone)) {
            $timeZone = new DateTimeZone('UTC');
        }
        $this->setTimeZone($timeZone);
    }

    
    public function setBaseObject(Document $vcalendar)
    {
        $this->baseObject = $vcalendar;
    }

    
    public function setVAvailability(Document $vcalendar)
    {
        $this->vavailability = $vcalendar;
    }

    
    public function setObjects($objects)
    {
        if (!is_array($objects)) {
            $objects = [$objects];
        }

        $this->objects = [];
        foreach ($objects as $object) {
            if (is_string($object) || is_resource($object)) {
                $this->objects[] = Reader::read($object);
            } elseif ($object instanceof Component) {
                $this->objects[] = $object;
            } else {
                throw new \InvalidArgumentException('You can only pass strings or \\Sabre\\VObject\\Component arguments to setObjects');
            }
        }
    }

    
    public function setTimeRange(DateTimeInterface $start = null, DateTimeInterface $end = null)
    {
        if (!$start) {
            $start = new DateTimeImmutable(Settings::$minDate);
        }
        if (!$end) {
            $end = new DateTimeImmutable(Settings::$maxDate);
        }
        $this->start = $start;
        $this->end = $end;
    }

    
    public function setTimeZone(DateTimeZone $timeZone)
    {
        $this->timeZone = $timeZone;
    }

    
    public function getResult()
    {
        $fbData = new FreeBusyData(
            $this->start->getTimeStamp(),
            $this->end->getTimeStamp()
        );
        if ($this->vavailability) {
            $this->calculateAvailability($fbData, $this->vavailability);
        }

        $this->calculateBusy($fbData, $this->objects);

        return $this->generateFreeBusyCalendar($fbData);
    }

    
    protected function calculateAvailability(FreeBusyData $fbData, VCalendar $vavailability)
    {
        $vavailComps = iterator_to_array($vavailability->VAVAILABILITY);
        usort(
            $vavailComps,
            function ($a, $b) {
                                                                                                $priorityA = isset($a->PRIORITY) ? (int) $a->PRIORITY->getValue() : 0;
                $priorityB = isset($b->PRIORITY) ? (int) $b->PRIORITY->getValue() : 0;

                if (0 === $priorityA) {
                    $priorityA = 10;
                }
                if (0 === $priorityB) {
                    $priorityB = 10;
                }

                return $priorityA - $priorityB;
            }
        );

                                                                $old = $vavailComps;
        $new = [];

        foreach ($old as $vavail) {
            list($compStart, $compEnd) = $vavail->getEffectiveStartEnd();

                                                if (is_null($compStart) || $compStart < $this->start) {
                $compStart = $this->start;
            }
            if (is_null($compEnd) || $compEnd > $this->end) {
                $compEnd = $this->end;
            }

                        if ($compStart > $this->end || $compEnd < $this->start) {
                continue;
            }

                                    foreach ($new as $higherVavail) {
                list($higherStart, $higherEnd) = $higherVavail->getEffectiveStartEnd();
                if (
                    (is_null($higherStart) || $higherStart < $compStart) &&
                    (is_null($higherEnd) || $higherEnd > $compEnd)
                ) {
                                                            continue 2;
                }
            }

                        $new[] = $vavail;
        }

                                                foreach (array_reverse($new) as $vavail) {
            $busyType = isset($vavail->BUSYTYPE) ? strtoupper($vavail->BUSYTYPE) : 'BUSY-UNAVAILABLE';
            list($vavailStart, $vavailEnd) = $vavail->getEffectiveStartEnd();

                                    if (!$vavailStart || $vavailStart < $this->start) {
                $vavailStart = $this->start;
            }
            if (!$vavailEnd || $vavailEnd > $this->end) {
                $vavailEnd = $this->end;
            }

                                    $fbData->add(
                $vavailStart->getTimeStamp(),
                $vavailEnd->getTimeStamp(),
                $busyType
            );

                        if (isset($vavail->AVAILABLE)) {
                foreach ($vavail->AVAILABLE as $available) {
                    list($availStart, $availEnd) = $available->getEffectiveStartEnd();
                    $fbData->add(
                    $availStart->getTimeStamp(),
                    $availEnd->getTimeStamp(),
                    'FREE'
                );

                    if ($available->RRULE) {
                        
                        $rruleIterator = new Recur\RRuleIterator(
                        $available->RRULE->getValue(),
                        $availStart
                    );
                        $rruleIterator->fastForward($vavailStart);

                        $startEndDiff = $availStart->diff($availEnd);

                        while ($rruleIterator->valid()) {
                            $recurStart = $rruleIterator->current();
                            $recurEnd = $recurStart->add($startEndDiff);

                            if ($recurStart > $vavailEnd) {
                                                                break;
                            }

                            if ($recurEnd > $vavailEnd) {
                                                                                                $recurEnd = $vavailEnd;
                            }

                            $fbData->add(
                            $recurStart->getTimeStamp(),
                            $recurEnd->getTimeStamp(),
                            'FREE'
                        );

                            $rruleIterator->next();
                        }
                    }
                }
            }
        }
    }

    
    protected function calculateBusy(FreeBusyData $fbData, array $objects)
    {
        foreach ($objects as $key => $object) {
            foreach ($object->getBaseComponents() as $component) {
                switch ($component->name) {
                    case 'VEVENT':

                        $FBTYPE = 'BUSY';
                        if (isset($component->TRANSP) && ('TRANSPARENT' === strtoupper($component->TRANSP))) {
                            break;
                        }
                        if (isset($component->STATUS)) {
                            $status = strtoupper($component->STATUS);
                            if ('CANCELLED' === $status) {
                                break;
                            }
                            if ('TENTATIVE' === $status) {
                                $FBTYPE = 'BUSY-TENTATIVE';
                            }
                        }

                        $times = [];

                        if ($component->RRULE) {
                            try {
                                $iterator = new EventIterator($object, (string) $component->UID, $this->timeZone);
                            } catch (NoInstancesException $e) {
                                                                                                                                unset($this->objects[$key]);
                                break;
                            }

                            if ($this->start) {
                                $iterator->fastForward($this->start);
                            }

                            $maxRecurrences = Settings::$maxRecurrences;

                            while ($iterator->valid() && --$maxRecurrences) {
                                $startTime = $iterator->getDTStart();
                                if ($this->end && $startTime > $this->end) {
                                    break;
                                }
                                $times[] = [
                                    $iterator->getDTStart(),
                                    $iterator->getDTEnd(),
                                ];

                                $iterator->next();
                            }
                        } else {
                            $startTime = $component->DTSTART->getDateTime($this->timeZone);
                            if ($this->end && $startTime > $this->end) {
                                break;
                            }
                            $endTime = null;
                            if (isset($component->DTEND)) {
                                $endTime = $component->DTEND->getDateTime($this->timeZone);
                            } elseif (isset($component->DURATION)) {
                                $duration = DateTimeParser::parseDuration((string) $component->DURATION);
                                $endTime = clone $startTime;
                                $endTime = $endTime->add($duration);
                            } elseif (!$component->DTSTART->hasTime()) {
                                $endTime = clone $startTime;
                                $endTime = $endTime->modify('+1 day');
                            } else {
                                                                break;
                            }

                            $times[] = [$startTime, $endTime];
                        }

                        foreach ($times as $time) {
                            if ($this->end && $time[0] > $this->end) {
                                break;
                            }
                            if ($this->start && $time[1] < $this->start) {
                                break;
                            }

                            $fbData->add(
                                $time[0]->getTimeStamp(),
                                $time[1]->getTimeStamp(),
                                $FBTYPE
                            );
                        }
                        break;

                    case 'VFREEBUSY':
                        foreach ($component->FREEBUSY as $freebusy) {
                            $fbType = isset($freebusy['FBTYPE']) ? strtoupper($freebusy['FBTYPE']) : 'BUSY';

                                                        if ('FREE' === $fbType) {
                                continue;
                            }

                            $values = explode(',', $freebusy);
                            foreach ($values as $value) {
                                list($startTime, $endTime) = explode('/', $value);
                                $startTime = DateTimeParser::parseDateTime($startTime);

                                if ('P' === substr($endTime, 0, 1) || '-P' === substr($endTime, 0, 2)) {
                                    $duration = DateTimeParser::parseDuration($endTime);
                                    $endTime = clone $startTime;
                                    $endTime = $endTime->add($duration);
                                } else {
                                    $endTime = DateTimeParser::parseDateTime($endTime);
                                }

                                if ($this->start && $this->start > $endTime) {
                                    continue;
                                }
                                if ($this->end && $this->end < $startTime) {
                                    continue;
                                }
                                $fbData->add(
                                    $startTime->getTimeStamp(),
                                    $endTime->getTimeStamp(),
                                    $fbType
                                );
                            }
                        }
                        break;
                }
            }
        }
    }

    
    protected function generateFreeBusyCalendar(FreeBusyData $fbData)
    {
        if ($this->baseObject) {
            $calendar = $this->baseObject;
        } else {
            $calendar = new VCalendar();
        }

        $vfreebusy = $calendar->createComponent('VFREEBUSY');
        $calendar->add($vfreebusy);

        if ($this->start) {
            $dtstart = $calendar->createProperty('DTSTART');
            $dtstart->setDateTime($this->start);
            $vfreebusy->add($dtstart);
        }
        if ($this->end) {
            $dtend = $calendar->createProperty('DTEND');
            $dtend->setDateTime($this->end);
            $vfreebusy->add($dtend);
        }

        $tz = new \DateTimeZone('UTC');
        $dtstamp = $calendar->createProperty('DTSTAMP');
        $dtstamp->setDateTime(new DateTimeImmutable('now', $tz));
        $vfreebusy->add($dtstamp);

        foreach ($fbData->getData() as $busyTime) {
            $busyType = strtoupper($busyTime['type']);

                        if ('FREE' === $busyType) {
                continue;
            }

            $busyTime[0] = new \DateTimeImmutable('@'.$busyTime['start'], $tz);
            $busyTime[1] = new \DateTimeImmutable('@'.$busyTime['end'], $tz);

            $prop = $calendar->createProperty(
                'FREEBUSY',
                $busyTime[0]->format('Ymd\\THis\\Z').'/'.$busyTime[1]->format('Ymd\\THis\\Z')
            );

                                    if ('BUSY' !== $busyType) {
                $prop['FBTYPE'] = $busyType;
            }
            $vfreebusy->add($prop);
        }

        return $calendar;
    }
}
