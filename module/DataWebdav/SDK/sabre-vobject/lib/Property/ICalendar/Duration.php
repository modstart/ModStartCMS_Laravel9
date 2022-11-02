<?php

namespace Sabre\VObject\Property\ICalendar;

use Sabre\VObject\DateTimeParser;
use Sabre\VObject\Property;


class Duration extends Property
{
    
    public $delimiter = ',';

    
    public function setRawMimeDirValue($val)
    {
        $this->setValue(explode($this->delimiter, $val));
    }

    
    public function getRawMimeDirValue()
    {
        return implode($this->delimiter, $this->getParts());
    }

    
    public function getValueType()
    {
        return 'DURATION';
    }

    
    public function getDateInterval()
    {
        $parts = $this->getParts();
        $value = $parts[0];

        return DateTimeParser::parseDuration($value);
    }
}
