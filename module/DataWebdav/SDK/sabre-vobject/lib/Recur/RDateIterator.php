<?php

namespace Sabre\VObject\Recur;

use DateTimeInterface;
use Iterator;
use Sabre\VObject\DateTimeParser;


class RDateIterator implements Iterator
{
    
    public function __construct($rrule, DateTimeInterface $start)
    {
        $this->startDate = $start;
        $this->parseRDate($rrule);
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
        return $this->counter <= count($this->dates);
    }

    
    public function rewind()
    {
        $this->currentDate = clone $this->startDate;
        $this->counter = 0;
    }

    
    public function next()
    {
        ++$this->counter;
        if (!$this->valid()) {
            return;
        }

        $this->currentDate =
            DateTimeParser::parse(
                $this->dates[$this->counter - 1],
                $this->startDate->getTimezone()
            );
    }

    

    
    public function isInfinite()
    {
        return false;
    }

    
    public function fastForward(DateTimeInterface $dt)
    {
        while ($this->valid() && $this->currentDate < $dt) {
            $this->next();
        }
    }

    
    protected $startDate;

    
    protected $currentDate;

    
    protected $counter = 0;

    

    
    protected function parseRDate($rdate)
    {
        if (is_string($rdate)) {
            $rdate = explode(',', $rdate);
        }

        $this->dates = $rdate;
    }

    
    protected $dates = [];
}
