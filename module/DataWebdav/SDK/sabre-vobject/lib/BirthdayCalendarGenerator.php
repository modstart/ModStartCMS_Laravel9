<?php

namespace Sabre\VObject;

use Sabre\VObject\Component\VCalendar;


class BirthdayCalendarGenerator
{
    
    protected $objects = [];

    
    const DEFAULT_YEAR = 2000;

    
    protected $format = '%1$s\'s Birthday';

    
    public function __construct($objects = null)
    {
        if ($objects) {
            $this->setObjects($objects);
        }
    }

    
    public function setObjects($objects)
    {
        if (!is_array($objects)) {
            $objects = [$objects];
        }

        $this->objects = [];
        foreach ($objects as $object) {
            if (is_string($object)) {
                $vObj = Reader::read($object);
                if (!$vObj instanceof Component\VCard) {
                    throw new \InvalidArgumentException('String could not be parsed as \\Sabre\\VObject\\Component\\VCard by setObjects');
                }

                $this->objects[] = $vObj;
            } elseif ($object instanceof Component\VCard) {
                $this->objects[] = $object;
            } else {
                throw new \InvalidArgumentException('You can only pass strings or \\Sabre\\VObject\\Component\\VCard arguments to setObjects');
            }
        }
    }

    
    public function setFormat($format)
    {
        $this->format = $format;
    }

    
    public function getResult()
    {
        $calendar = new VCalendar();

        foreach ($this->objects as $object) {
                        if (!$object->select('BDAY')) {
                continue;
            }

                                                if (empty($object->BDAY->getValue())) {
                continue;
            }

                                    $object = $object->convert(Document::VCARD40);

                        if (!isset($object->FN)) {
                continue;
            }

                        if (!$object->BDAY instanceof Property\VCard\DateAndOrTime) {
                continue;
            }

                        try {
                $dateParts = DateTimeParser::parseVCardDateTime($object->BDAY->getValue());
            } catch (InvalidDataException $e) {
                continue;
            }

                        $unknownYear = false;

            if (!$dateParts['year']) {
                $object->BDAY = self::DEFAULT_YEAR.'-'.$dateParts['month'].'-'.$dateParts['date'];

                $unknownYear = true;
            }

                        $event = $calendar->add('VEVENT', [
                'SUMMARY' => sprintf($this->format, $object->FN->getValue()),
                'DTSTART' => new \DateTime($object->BDAY->getValue()),
                'RRULE' => 'FREQ=YEARLY',
                'TRANSP' => 'TRANSPARENT',
            ]);

                        $event->DTSTART['VALUE'] = 'DATE';

                        if ($unknownYear) {
                $event->add('X-SABRE-BDAY', 'BDAY', [
                    'X-SABRE-VCARD-UID' => $object->UID->getValue(),
                    'X-SABRE-VCARD-FN' => $object->FN->getValue(),
                    'X-SABRE-OMIT-YEAR' => self::DEFAULT_YEAR,
                ]);
            } else {
                $event->add('X-SABRE-BDAY', 'BDAY', [
                    'X-SABRE-VCARD-UID' => $object->UID->getValue(),
                    'X-SABRE-VCARD-FN' => $object->FN->getValue(),
                ]);
            }
        }

        return $calendar;
    }
}
