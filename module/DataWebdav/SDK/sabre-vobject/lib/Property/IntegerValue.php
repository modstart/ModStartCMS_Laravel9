<?php

namespace Sabre\VObject\Property;

use
    Sabre\VObject\Property;


class IntegerValue extends Property
{
    
    public function setRawMimeDirValue($val)
    {
        $this->setValue((int) $val);
    }

    
    public function getRawMimeDirValue()
    {
        return $this->value;
    }

    
    public function getValueType()
    {
        return 'INTEGER';
    }

    
    public function getJsonValue()
    {
        return [(int) $this->getValue()];
    }

    
    public function setXmlValue(array $value)
    {
        $value = array_map('intval', $value);
        parent::setXmlValue($value);
    }
}
