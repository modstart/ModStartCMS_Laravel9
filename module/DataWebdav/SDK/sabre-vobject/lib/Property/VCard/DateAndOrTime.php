<?php

namespace Sabre\VObject\Property\VCard;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Sabre\VObject\DateTimeParser;
use Sabre\VObject\InvalidDataException;
use Sabre\VObject\Property;
use Sabre\Xml;


class DateAndOrTime extends Property
{
    
    public $delimiter = null;

    
    public function getValueType()
    {
        return 'DATE-AND-OR-TIME';
    }

    
    public function setParts(array $parts)
    {
        if (count($parts) > 1) {
            throw new \InvalidArgumentException('Only one value allowed');
        }
        if (isset($parts[0]) && $parts[0] instanceof DateTimeInterface) {
            $this->setDateTime($parts[0]);
        } else {
            parent::setParts($parts);
        }
    }

    
    public function setValue($value)
    {
        if ($value instanceof DateTimeInterface) {
            $this->setDateTime($value);
        } else {
            parent::setValue($value);
        }
    }

    
    public function setDateTime(DateTimeInterface $dt)
    {
        $tz = $dt->getTimeZone();
        $isUtc = in_array($tz->getName(), ['UTC', 'GMT', 'Z']);

        if ($isUtc) {
            $value = $dt->format('Ymd\\THis\\Z');
        } else {
                        $value = $dt->format('Ymd\\THisO');
        }

        $this->value = $value;
    }

    
    public function getDateTime()
    {
        $now = new DateTime();

        $tzFormat = 0 === $now->getTimezone()->getOffset($now) ? '\\Z' : 'O';
        $nowParts = DateTimeParser::parseVCardDateTime($now->format('Ymd\\This'.$tzFormat));

        $dateParts = DateTimeParser::parseVCardDateTime($this->getValue());

                                foreach ($dateParts as $k => $v) {
            if (is_null($v)) {
                $dateParts[$k] = $nowParts[$k];
            }
        }

        return new DateTimeImmutable("$dateParts[year]-$dateParts[month]-$dateParts[date] $dateParts[hour]:$dateParts[minute]:$dateParts[second] $dateParts[timezone]");
    }

    
    public function getJsonValue()
    {
        $parts = DateTimeParser::parseVCardDateTime($this->getValue());

        $dateStr = '';

                if (!is_null($parts['year'])) {
            $dateStr .= $parts['year'];

            if (!is_null($parts['month'])) {
                                                $dateStr .= '-';
            }
        } else {
            if (!is_null($parts['month']) || !is_null($parts['date'])) {
                                $dateStr .= '--';
            }
        }

                if (!is_null($parts['month'])) {
            $dateStr .= $parts['month'];

            if (isset($parts['date'])) {
                                $dateStr .= '-';
            }
        } elseif (isset($parts['date'])) {
                                    $dateStr .= '-';
        }

                if (!is_null($parts['date'])) {
            $dateStr .= $parts['date'];
        }

                if (is_null($parts['hour']) && is_null($parts['minute']) && is_null($parts['second'])) {
            return [$dateStr];
        }

        $dateStr .= 'T';

                if (!is_null($parts['hour'])) {
            $dateStr .= $parts['hour'];

            if (!is_null($parts['minute'])) {
                $dateStr .= ':';
            }
        } else {
                                    $dateStr .= '-';
        }

                if (!is_null($parts['minute'])) {
            $dateStr .= $parts['minute'];

            if (!is_null($parts['second'])) {
                $dateStr .= ':';
            }
        } elseif (isset($parts['second'])) {
                        $dateStr .= '-';
        }

                if (!is_null($parts['second'])) {
            $dateStr .= $parts['second'];
        }

                if (!is_null($parts['timezone'])) {
            $dateStr .= $parts['timezone'];
        }

        return [$dateStr];
    }

    
    protected function xmlSerializeValue(Xml\Writer $writer)
    {
        $valueType = strtolower($this->getValueType());
        $parts = DateTimeParser::parseVCardDateAndOrTime($this->getValue());
        $value = '';

                $d = function ($part) use ($parts) {
            return !is_null($parts[$part]);
        };

                $r = function ($part) use ($parts) {
            return $parts[$part];
        };

                                                        if (($d('year') || $d('month') || $d('date'))
            && (!$d('hour') && !$d('minute') && !$d('second') && !$d('timezone'))) {
            if ($d('year') && $d('month') && $d('date')) {
                $value .= $r('year').$r('month').$r('date');
            } elseif ($d('year') && $d('month') && !$d('date')) {
                $value .= $r('year').'-'.$r('month');
            } elseif (!$d('year') && $d('month')) {
                $value .= '--'.$r('month').$r('date');
            } elseif (!$d('year') && !$d('month') && $d('date')) {
                $value .= '---'.$r('date');
            }

                                                    } elseif ((!$d('year') && !$d('month') && !$d('date'))
                  && ($d('hour') || $d('minute') || $d('second'))) {
            if ($d('hour')) {
                $value .= $r('hour').$r('minute').$r('second');
            } elseif ($d('minute')) {
                $value .= '-'.$r('minute').$r('second');
            } elseif ($d('second')) {
                $value .= '--'.$r('second');
            }

            $value .= $r('timezone');

                                                } elseif ($d('date') && $d('hour')) {
            if ($d('year') && $d('month') && $d('date')) {
                $value .= $r('year').$r('month').$r('date');
            } elseif (!$d('year') && $d('month') && $d('date')) {
                $value .= '--'.$r('month').$r('date');
            } elseif (!$d('year') && !$d('month') && $d('date')) {
                $value .= '---'.$r('date');
            }

            $value .= 'T'.$r('hour').$r('minute').$r('second').
                      $r('timezone');
        }

        $writer->writeElement($valueType, $value);
    }

    
    public function setRawMimeDirValue($val)
    {
        $this->setValue($val);
    }

    
    public function getRawMimeDirValue()
    {
        return implode($this->delimiter, $this->getParts());
    }

    
    public function validate($options = 0)
    {
        $messages = parent::validate($options);
        $value = $this->getValue();

        try {
            DateTimeParser::parseVCardDateTime($value);
        } catch (InvalidDataException $e) {
            $messages[] = [
                'level' => 3,
                'message' => 'The supplied value ('.$value.') is not a correct DATE-AND-OR-TIME property',
                'node' => $this,
            ];
        }

        return $messages;
    }
}
