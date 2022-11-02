<?php

namespace Sabre\VObject\Property\VCard;

use Sabre\VObject\DateTimeParser;
use Sabre\VObject\Property\Text;
use Sabre\Xml;


class TimeStamp extends Text
{
    
    public $delimiter = null;

    
    public function getValueType()
    {
        return 'TIMESTAMP';
    }

    
    public function getJsonValue()
    {
        $parts = DateTimeParser::parseVCardDateTime($this->getValue());

        $dateStr =
            $parts['year'].'-'.
            $parts['month'].'-'.
            $parts['date'].'T'.
            $parts['hour'].':'.
            $parts['minute'].':'.
            $parts['second'];

                if (!is_null($parts['timezone'])) {
            $dateStr .= $parts['timezone'];
        }

        return [$dateStr];
    }

    
    protected function xmlSerializeValue(Xml\Writer $writer)
    {
                        $valueType = strtolower($this->getValueType());
        $writer->writeElement($valueType, $this->getValue());
    }
}
