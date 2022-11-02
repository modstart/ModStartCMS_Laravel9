<?php

namespace Sabre\VObject\Property;

use
    Sabre\VObject\Property;


class Boolean extends Property
{
    
    public function setRawMimeDirValue($val)
    {
        $val = 'TRUE' === strtoupper($val) ? true : false;
        $this->setValue($val);
    }

    
    public function getRawMimeDirValue()
    {
        return $this->value ? 'TRUE' : 'FALSE';
    }

    
    public function getValueType()
    {
        return 'BOOLEAN';
    }

    
    public function setXmlValue(array $value)
    {
        $value = array_map(
            function ($value) {
                return 'true' === $value;
            },
            $value
        );
        parent::setXmlValue($value);
    }
}
