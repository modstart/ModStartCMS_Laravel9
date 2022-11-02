<?php

namespace Sabre\VObject\Property;

use Sabre\VObject\Property;
use Sabre\Xml;


class FloatValue extends Property
{
    
    public $delimiter = ';';

    
    public function setRawMimeDirValue($val)
    {
        $val = explode($this->delimiter, $val);
        foreach ($val as &$item) {
            $item = (float) $item;
        }
        $this->setParts($val);
    }

    
    public function getRawMimeDirValue()
    {
        return implode(
            $this->delimiter,
            $this->getParts()
        );
    }

    
    public function getValueType()
    {
        return 'FLOAT';
    }

    
    public function getJsonValue()
    {
        $val = array_map('floatval', $this->getParts());

                                        if ('GEO' === $this->name) {
            return [$val];
        }

        return $val;
    }

    
    public function setXmlValue(array $value)
    {
        $value = array_map('floatval', $value);
        parent::setXmlValue($value);
    }

    
    protected function xmlSerializeValue(Xml\Writer $writer)
    {
                                        if ('GEO' === $this->name) {
            $value = array_map('floatval', $this->getParts());

            $writer->writeElement('latitude', $value[0]);
            $writer->writeElement('longitude', $value[1]);
        } else {
            parent::xmlSerializeValue($writer);
        }
    }
}
