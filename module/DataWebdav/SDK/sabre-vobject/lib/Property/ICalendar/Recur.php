<?php

namespace Sabre\VObject\Property\ICalendar;

use Sabre\VObject\Property;
use Sabre\Xml;


class Recur extends Property
{
    
    public function setValue($value)
    {
                if ($value instanceof \StdClass) {
            $value = (array) $value;
        }

        if (is_array($value)) {
            $newVal = [];
            foreach ($value as $k => $v) {
                if (is_string($v)) {
                    $v = strtoupper($v);

                                        if (false !== strpos($v, ',')) {
                        $v = explode(',', $v);
                    }
                    if (0 === strcmp($k, 'until')) {
                        $v = strtr($v, [':' => '', '-' => '']);
                    }
                } elseif (is_array($v)) {
                    $v = array_map('strtoupper', $v);
                }

                $newVal[strtoupper($k)] = $v;
            }
            $this->value = $newVal;
        } elseif (is_string($value)) {
            $this->value = self::stringToArray($value);
        } else {
            throw new \InvalidArgumentException('You must either pass a string, or a key=>value array');
        }
    }

    
    public function getValue()
    {
        $out = [];
        foreach ($this->value as $key => $value) {
            $out[] = $key.'='.(is_array($value) ? implode(',', $value) : $value);
        }

        return strtoupper(implode(';', $out));
    }

    
    public function setParts(array $parts)
    {
        $this->setValue($parts);
    }

    
    public function getParts()
    {
        return $this->value;
    }

    
    public function setRawMimeDirValue($val)
    {
        $this->setValue($val);
    }

    
    public function getRawMimeDirValue()
    {
        return $this->getValue();
    }

    
    public function getValueType()
    {
        return 'RECUR';
    }

    
    public function getJsonValue()
    {
        $values = [];
        foreach ($this->getParts() as $k => $v) {
            if (0 === strcmp($k, 'UNTIL')) {
                $date = new DateTime($this->root, null, $v);
                $values[strtolower($k)] = $date->getJsonValue()[0];
            } elseif (0 === strcmp($k, 'COUNT')) {
                $values[strtolower($k)] = intval($v);
            } else {
                $values[strtolower($k)] = $v;
            }
        }

        return [$values];
    }

    
    protected function xmlSerializeValue(Xml\Writer $writer)
    {
        $valueType = strtolower($this->getValueType());

        foreach ($this->getJsonValue() as $value) {
            $writer->writeElement($valueType, $value);
        }
    }

    
    public static function stringToArray($value)
    {
        $value = strtoupper($value);
        $newValue = [];
        foreach (explode(';', $value) as $part) {
                        if (empty($part)) {
                continue;
            }
            list($partName, $partValue) = explode('=', $part);

                        if (false !== strpos($partValue, ',')) {
                $partValue = explode(',', $partValue);
            }
            $newValue[$partName] = $partValue;
        }

        return $newValue;
    }

    
    public function validate($options = 0)
    {
        $repair = ($options & self::REPAIR);

        $warnings = parent::validate($options);
        $values = $this->getParts();

        foreach ($values as $key => $value) {
            if ('' === $value) {
                $warnings[] = [
                    'level' => $repair ? 1 : 3,
                    'message' => 'Invalid value for '.$key.' in '.$this->name,
                    'node' => $this,
                ];
                if ($repair) {
                    unset($values[$key]);
                }
            } elseif ('BYMONTH' == $key) {
                $byMonth = (array) $value;
                foreach ($byMonth as $i => $v) {
                    if (!is_numeric($v) || (int) $v < 1 || (int) $v > 12) {
                        $warnings[] = [
                            'level' => $repair ? 1 : 3,
                            'message' => 'BYMONTH in RRULE must have value(s) between 1 and 12!',
                            'node' => $this,
                        ];
                        if ($repair) {
                            if (is_array($value)) {
                                unset($values[$key][$i]);
                            } else {
                                unset($values[$key]);
                            }
                        }
                    }
                }
                                if (is_array($value) && empty($values[$key])) {
                    unset($values[$key]);
                }
            } elseif ('BYWEEKNO' == $key) {
                $byWeekNo = (array) $value;
                foreach ($byWeekNo as $i => $v) {
                    if (!is_numeric($v) || (int) $v < -53 || 0 == (int) $v || (int) $v > 53) {
                        $warnings[] = [
                            'level' => $repair ? 1 : 3,
                            'message' => 'BYWEEKNO in RRULE must have value(s) from -53 to -1, or 1 to 53!',
                            'node' => $this,
                        ];
                        if ($repair) {
                            if (is_array($value)) {
                                unset($values[$key][$i]);
                            } else {
                                unset($values[$key]);
                            }
                        }
                    }
                }
                                if (is_array($value) && empty($values[$key])) {
                    unset($values[$key]);
                }
            } elseif ('BYYEARDAY' == $key) {
                $byYearDay = (array) $value;
                foreach ($byYearDay as $i => $v) {
                    if (!is_numeric($v) || (int) $v < -366 || 0 == (int) $v || (int) $v > 366) {
                        $warnings[] = [
                            'level' => $repair ? 1 : 3,
                            'message' => 'BYYEARDAY in RRULE must have value(s) from -366 to -1, or 1 to 366!',
                            'node' => $this,
                        ];
                        if ($repair) {
                            if (is_array($value)) {
                                unset($values[$key][$i]);
                            } else {
                                unset($values[$key]);
                            }
                        }
                    }
                }
                                if (is_array($value) && empty($values[$key])) {
                    unset($values[$key]);
                }
            }
        }
        if (!isset($values['FREQ'])) {
            $warnings[] = [
                'level' => $repair ? 1 : 3,
                'message' => 'FREQ is required in '.$this->name,
                'node' => $this,
            ];
            if ($repair) {
                $this->parent->remove($this);
            }
        }
        if ($repair) {
            $this->setValue($values);
        }

        return $warnings;
    }
}
