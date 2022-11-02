<?php

namespace Sabre\VObject\Property;


class UtcOffset extends Text
{
    
    public $delimiter = null;

    
    public function getValueType()
    {
        return 'UTC-OFFSET';
    }

    
    public function setJsonValue(array $value)
    {
        $value = array_map(
            function ($value) {
                return str_replace(':', '', $value);
            },
            $value
        );
        parent::setJsonValue($value);
    }

    
    public function getJsonValue()
    {
        return array_map(
            function ($value) {
                return substr($value, 0, -2).':'.
                       substr($value, -2);
            },
            parent::getJsonValue()
        );
    }
}
