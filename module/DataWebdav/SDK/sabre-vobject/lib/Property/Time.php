<?php

namespace Sabre\VObject\Property;

use Sabre\VObject\DateTimeParser;


class Time extends Text
{
    
    public $delimiter = null;

    
    public function getValueType()
    {
        return 'TIME';
    }

    
    public function setJsonValue(array $value)
    {
                $value = str_replace(
            ':',
            '',
            $value
        );

        if (1 === count($value)) {
            $this->setValue(reset($value));
        } else {
            $this->setValue($value);
        }
    }

    
    public function getJsonValue()
    {
        $parts = DateTimeParser::parseVCardTime($this->getValue());
        $timeStr = '';

                if (!is_null($parts['hour'])) {
            $timeStr .= $parts['hour'];

            if (!is_null($parts['minute'])) {
                $timeStr .= ':';
            }
        } else {
                                    $timeStr .= '-';
        }

                if (!is_null($parts['minute'])) {
            $timeStr .= $parts['minute'];

            if (!is_null($parts['second'])) {
                $timeStr .= ':';
            }
        } else {
            if (isset($parts['second'])) {
                                $timeStr .= '-';
            }
        }

                if (!is_null($parts['second'])) {
            $timeStr .= $parts['second'];
        }

                if (!is_null($parts['timezone'])) {
            if ('Z' === $parts['timezone']) {
                $timeStr .= 'Z';
            } else {
                $timeStr .=
                    preg_replace('/([0-9]{2})([0-9]{2})$/', '$1:$2', $parts['timezone']);
            }
        }

        return [$timeStr];
    }

    
    public function setXmlValue(array $value)
    {
        $value = array_map(
            function ($value) {
                return str_replace(':', '', $value);
            },
            $value
        );
        parent::setXmlValue($value);
    }
}
