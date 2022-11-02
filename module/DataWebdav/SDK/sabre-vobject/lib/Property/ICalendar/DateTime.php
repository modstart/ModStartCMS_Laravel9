<?php

namespace Sabre\VObject\Property\ICalendar;

use DateTimeInterface;
use DateTimeZone;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\Property;
use Sabre\VObject\TimeZoneUtil;


class DateTime extends Property
{
    
    public $delimiter = ',';

    
    public function setParts(array $parts)
    {
        if (isset($parts[0]) && $parts[0] instanceof DateTimeInterface) {
            $this->setDateTimes($parts);
        } else {
            parent::setParts($parts);
        }
    }

    
    public function setValue($value)
    {
        if (is_array($value) && isset($value[0]) && $value[0] instanceof DateTimeInterface) {
            $this->setDateTimes($value);
        } elseif ($value instanceof DateTimeInterface) {
            $this->setDateTimes([$value]);
        } else {
            parent::setValue($value);
        }
    }

    
    public function setRawMimeDirValue($val)
    {
        $this->setValue(explode($this->delimiter, $val));
    }

    
    public function getRawMimeDirValue()
    {
        return implode($this->delimiter, $this->getParts());
    }

    
    public function hasTime()
    {
        return 'DATE' !== strtoupper((string) $this['VALUE']);
    }

    
    public function isFloating()
    {
        return
            !$this->hasTime() ||
            (
                !isset($this['TZID']) &&
                false === strpos($this->getValue(), 'Z')
            );
    }

    
    public function getDateTime(DateTimeZone $timeZone = null)
    {
        $dt = $this->getDateTimes($timeZone);
        if (!$dt) {
            return;
        }

        return $dt[0];
    }

    
    public function getDateTimes(DateTimeZone $timeZone = null)
    {
                $tzid = $this['TZID'];

        if ($tzid) {
            $timeZone = TimeZoneUtil::getTimeZone((string) $tzid, $this->root);
        }

        $dts = [];
        foreach ($this->getParts() as $part) {
            $dts[] = DateTimeParser::parse($part, $timeZone);
        }

        return $dts;
    }

    
    public function setDateTime(DateTimeInterface $dt, $isFloating = false)
    {
        $this->setDateTimes([$dt], $isFloating);
    }

    
    public function setDateTimes(array $dt, $isFloating = false)
    {
        $values = [];

        if ($this->hasTime()) {
            $tz = null;
            $isUtc = false;

            foreach ($dt as $d) {
                if ($isFloating) {
                    $values[] = $d->format('Ymd\\THis');
                    continue;
                }
                if (is_null($tz)) {
                    $tz = $d->getTimeZone();
                    $isUtc = in_array($tz->getName(), ['UTC', 'GMT', 'Z', '+00:00']);
                    if (!$isUtc) {
                        $this->offsetSet('TZID', $tz->getName());
                    }
                } else {
                    $d = $d->setTimeZone($tz);
                }

                if ($isUtc) {
                    $values[] = $d->format('Ymd\\THis\\Z');
                } else {
                    $values[] = $d->format('Ymd\\THis');
                }
            }
            if ($isUtc || $isFloating) {
                $this->offsetUnset('TZID');
            }
        } else {
            foreach ($dt as $d) {
                $values[] = $d->format('Ymd');
            }
            $this->offsetUnset('TZID');
        }

        $this->value = $values;
    }

    
    public function getValueType()
    {
        return $this->hasTime() ? 'DATE-TIME' : 'DATE';
    }

    
    public function getJsonValue()
    {
        $dts = $this->getDateTimes();
        $hasTime = $this->hasTime();
        $isFloating = $this->isFloating();

        $tz = $dts[0]->getTimeZone();
        $isUtc = $isFloating ? false : in_array($tz->getName(), ['UTC', 'GMT', 'Z']);

        return array_map(
            function (DateTimeInterface $dt) use ($hasTime, $isUtc) {
                if ($hasTime) {
                    return $dt->format('Y-m-d\\TH:i:s').($isUtc ? 'Z' : '');
                } else {
                    return $dt->format('Y-m-d');
                }
            },
            $dts
        );
    }

    
    public function setJsonValue(array $value)
    {
                                        $this->setValue(
            array_map(
                function ($item) {
                    return strtr($item, [':' => '', '-' => '']);
                },
                $value
            )
        );
    }

    
    public function offsetSet($name, $value)
    {
        parent::offsetSet($name, $value);
        if ('VALUE' !== strtoupper($name)) {
            return;
        }

                $this->setDateTimes($this->getDateTimes());
    }

    
    public function validate($options = 0)
    {
        $messages = parent::validate($options);
        $valueType = $this->getValueType();
        $values = $this->getParts();
        try {
            foreach ($values as $value) {
                switch ($valueType) {
                    case 'DATE':
                        DateTimeParser::parseDate($value);
                        break;
                    case 'DATE-TIME':
                        DateTimeParser::parseDateTime($value);
                        break;
                }
            }
        } catch (InvalidDataException $e) {
            $messages[] = [
                'level' => 3,
                'message' => 'The supplied value ('.$value.') is not a correct '.$valueType,
                'node' => $this,
            ];
        }

        return $messages;
    }
}
