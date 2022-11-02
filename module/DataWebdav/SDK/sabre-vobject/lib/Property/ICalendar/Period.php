<?php

namespace Sabre\VObject\Property\ICalendar;

use Sabre\VObject\DateTimeParser;
use Sabre\VObject\Property;
use Sabre\Xml;


class Period extends Property
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
        return 'PERIOD';
    }

    
    public function setJsonValue(array $value)
    {
        $value = array_map(
            function ($item) {
                return strtr(implode('/', $item), [':' => '', '-' => '']);
            },
            $value
        );
        parent::setJsonValue($value);
    }

    
    public function getJsonValue()
    {
        $return = [];
        foreach ($this->getParts() as $item) {
            list($start, $end) = explode('/', $item, 2);

            $start = DateTimeParser::parseDateTime($start);

                        if ('P' === $end[0]) {
                $return[] = [
                    $start->format('Y-m-d\\TH:i:s'),
                    $end,
                ];
            } else {
                $end = DateTimeParser::parseDateTime($end);
                $return[] = [
                    $start->format('Y-m-d\\TH:i:s'),
                    $end->format('Y-m-d\\TH:i:s'),
                ];
            }
        }

        return $return;
    }

    
    protected function xmlSerializeValue(Xml\Writer $writer)
    {
        $writer->startElement(strtolower($this->getValueType()));
        $value = $this->getJsonValue();
        $writer->writeElement('start', $value[0][0]);

        if ('P' === $value[0][1][0]) {
            $writer->writeElement('duration', $value[0][1]);
        } else {
            $writer->writeElement('end', $value[0][1]);
        }

        $writer->endElement();
    }
}
