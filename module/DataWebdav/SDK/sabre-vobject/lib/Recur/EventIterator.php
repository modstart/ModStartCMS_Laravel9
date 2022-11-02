<?php

namespace Sabre\VObject\Recur;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use Sabre\VObject\Component;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Settings;


class EventIterator implements \Iterator
{
    
    protected $timeZone;

    
    protected $allDay = false;

    
    public function __construct($input, $uid = null, DateTimeZone $timeZone = null)
    {
        if (is_null($timeZone)) {
            $timeZone = new DateTimeZone('UTC');
        }
        $this->timeZone = $timeZone;

        if (is_array($input)) {
            $events = $input;
        } elseif ($input instanceof VEvent) {
                        $events = [$input];
        } else {
                        $uid = (string) $uid;
            if (!$uid) {
                throw new InvalidArgumentException('The UID argument is required when a VCALENDAR is passed to this constructor');
            }
            if (!isset($input->VEVENT)) {
                throw new InvalidArgumentException('No events found in this calendar');
            }
            $events = $input->getByUID($uid);
        }

        foreach ($events as $vevent) {
            if (!isset($vevent->{'RECURRENCE-ID'})) {
                $this->masterEvent = $vevent;
            } else {
                $this->exceptions[
                    $vevent->{'RECURRENCE-ID'}->getDateTime($this->timeZone)->getTimeStamp()
                ] = true;
                $this->overriddenEvents[] = $vevent;
            }
        }

        if (!$this->masterEvent) {
                                                                                    if (!count($this->overriddenEvents)) {
                throw new InvalidArgumentException('This VCALENDAR did not have an event with UID: '.$uid);
            }
            $this->masterEvent = array_shift($this->overriddenEvents);
        }

        $this->startDate = $this->masterEvent->DTSTART->getDateTime($this->timeZone);
        $this->allDay = !$this->masterEvent->DTSTART->hasTime();

        if (isset($this->masterEvent->EXDATE)) {
            foreach ($this->masterEvent->EXDATE as $exDate) {
                foreach ($exDate->getDateTimes($this->timeZone) as $dt) {
                    $this->exceptions[$dt->getTimeStamp()] = true;
                }
            }
        }

        if (isset($this->masterEvent->DTEND)) {
            $this->eventDuration =
                $this->masterEvent->DTEND->getDateTime($this->timeZone)->getTimeStamp() -
                $this->startDate->getTimeStamp();
        } elseif (isset($this->masterEvent->DURATION)) {
            $duration = $this->masterEvent->DURATION->getDateInterval();
            $end = clone $this->startDate;
            $end = $end->add($duration);
            $this->eventDuration = $end->getTimeStamp() - $this->startDate->getTimeStamp();
        } elseif ($this->allDay) {
            $this->eventDuration = 3600 * 24;
        } else {
            $this->eventDuration = 0;
        }

        if (isset($this->masterEvent->RDATE)) {
            $this->recurIterator = new RDateIterator(
                $this->masterEvent->RDATE->getParts(),
                $this->startDate
            );
        } elseif (isset($this->masterEvent->RRULE)) {
            $this->recurIterator = new RRuleIterator(
                $this->masterEvent->RRULE->getParts(),
                $this->startDate
            );
        } else {
            $this->recurIterator = new RRuleIterator(
                [
                    'FREQ' => 'DAILY',
                    'COUNT' => 1,
                ],
                $this->startDate
            );
        }

        $this->rewind();
        if (!$this->valid()) {
            throw new NoInstancesException('This recurrence rule does not generate any valid instances');
        }
    }

    
    public function current()
    {
        if ($this->currentDate) {
            return clone $this->currentDate;
        }
    }

    
    public function getDtStart()
    {
        if ($this->currentDate) {
            return clone $this->currentDate;
        }
    }

    
    public function getDtEnd()
    {
        if (!$this->valid()) {
            return;
        }
        $end = clone $this->currentDate;

        return $end->modify('+'.$this->eventDuration.' seconds');
    }

    
    public function getEventObject()
    {
        if ($this->currentOverriddenEvent) {
            return $this->currentOverriddenEvent;
        }

        $event = clone $this->masterEvent;

                                        unset(
            $event->RRULE,
            $event->EXDATE,
            $event->RDATE,
            $event->EXRULE,
            $event->{'RECURRENCE-ID'}
        );
        
        $event->DTSTART->setDateTime($this->getDtStart(), $event->DTSTART->isFloating());
        if (isset($event->DTEND)) {
            $event->DTEND->setDateTime($this->getDtEnd(), $event->DTEND->isFloating());
        }
        $recurid = clone $event->DTSTART;
        $recurid->name = 'RECURRENCE-ID';
        $event->add($recurid);

        return $event;
    }

    
    public function key()
    {
                return $this->counter - 1;
    }

    
    public function valid()
    {
        if ($this->counter > Settings::$maxRecurrences && -1 !== Settings::$maxRecurrences) {
            throw new MaxInstancesExceededException('Recurring events are only allowed to generate '.Settings::$maxRecurrences);
        }

        return (bool) $this->currentDate;
    }

    
    public function rewind()
    {
        $this->recurIterator->rewind();
                $index = [];
        foreach ($this->overriddenEvents as $key => $event) {
            $stamp = $event->DTSTART->getDateTime($this->timeZone)->getTimeStamp();
            $index[$stamp][] = $key;
        }
        krsort($index);
        $this->counter = 0;
        $this->overriddenEventsIndex = $index;
        $this->currentOverriddenEvent = null;

        $this->nextDate = null;
        $this->currentDate = clone $this->startDate;

        $this->next();
    }

    
    public function next()
    {
        $this->currentOverriddenEvent = null;
        ++$this->counter;
        if ($this->nextDate) {
                        $nextDate = $this->nextDate;
            $this->nextDate = null;
        } else {
                                                do {
                if (!$this->recurIterator->valid()) {
                    $nextDate = null;
                    break;
                }
                $nextDate = $this->recurIterator->current();
                $this->recurIterator->next();
            } while (isset($this->exceptions[$nextDate->getTimeStamp()]));
        }

                        if ($this->overriddenEventsIndex) {
            $offsets = end($this->overriddenEventsIndex);
            $timestamp = key($this->overriddenEventsIndex);
            $offset = end($offsets);
            if (!$nextDate || $timestamp < $nextDate->getTimeStamp()) {
                                $this->currentOverriddenEvent = $this->overriddenEvents[$offset];

                                $this->nextDate = $nextDate;
                $this->currentDate = $this->currentOverriddenEvent->DTSTART->getDateTime($this->timeZone);

                                array_pop($this->overriddenEventsIndex[$timestamp]);
                if (!$this->overriddenEventsIndex[$timestamp]) {
                    array_pop($this->overriddenEventsIndex);
                }

                                return;
            }
        }

        $this->currentDate = $nextDate;
    }

    
    public function fastForward(DateTimeInterface $dateTime)
    {
        while ($this->valid() && $this->getDtEnd() <= $dateTime) {
            $this->next();
        }
    }

    
    public function isInfinite()
    {
        return $this->recurIterator->isInfinite();
    }

    
    protected $recurIterator;

    
    protected $eventDuration;

    
    protected $masterEvent;

    
    protected $overriddenEvents = [];

    
    protected $overriddenEventsIndex;

    
    protected $exceptions = [];

    
    protected $counter;

    
    protected $startDate;

    
    protected $currentDate;

    
    protected $nextDate;

    
    protected $currentOverriddenEvent;
}
