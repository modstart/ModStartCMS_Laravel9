<?php

namespace Sabre\VObject\Property;

use Sabre\VObject\Property;


class Binary extends Property
{
    
    public $delimiter = null;

    
    public function setValue($value)
    {
        if (is_array($value)) {
            if (1 === count($value)) {
                $this->value = $value[0];
            } else {
                throw new \InvalidArgumentException('The argument must either be a string or an array with only one child');
            }
        } else {
            $this->value = $value;
        }
    }

    
    public function setRawMimeDirValue($val)
    {
        $this->value = base64_decode($val);
    }

    
    public function getRawMimeDirValue()
    {
        return base64_encode($this->value);
    }

    
    public function getValueType()
    {
        return 'BINARY';
    }

    
    public function getJsonValue()
    {
        return [base64_encode($this->getValue())];
    }

    
    public function setJsonValue(array $value)
    {
        $value = array_map('base64_decode', $value);
        parent::setJsonValue($value);
    }
}
