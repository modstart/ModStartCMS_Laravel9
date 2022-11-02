<?php

namespace Sabre\VObject\Property\ICalendar;

use
    Sabre\VObject\Property\Text;


class CalAddress extends Text
{
    
    public $delimiter = null;

    
    public function getValueType()
    {
        return 'CAL-ADDRESS';
    }

    
    public function getNormalizedValue()
    {
        $input = $this->getValue();
        if (!strpos($input, ':')) {
            return $input;
        }
        list($schema, $everythingElse) = explode(':', $input, 2);

        return strtolower($schema).':'.$everythingElse;
    }
}
