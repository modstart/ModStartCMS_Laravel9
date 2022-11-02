<?php

namespace Sabre\VObject\Splitter;

use Sabre\VObject;
use Sabre\VObject\Component\VCalendar;


class ICalendar implements SplitterInterface
{
    
    protected $vtimezones = [];

    
    protected $objects = [];

    
    public function __construct($input, $options = 0)
    {
        $data = VObject\Reader::read($input, $options);

        if (!$data instanceof VObject\Component\VCalendar) {
            throw new VObject\ParseException('Supplied input could not be parsed as VCALENDAR.');
        }

        foreach ($data->children() as $component) {
            if (!$component instanceof VObject\Component) {
                continue;
            }

                        if ('VTIMEZONE' === $component->name) {
                $this->vtimezones[(string) $component->TZID] = $component;
                continue;
            }

                        if (!$component->UID) {
                $component->UID = sha1(microtime()).'-vobjectimport';
            }
            $uid = (string) $component->UID;

                        if (!array_key_exists($uid, $this->objects)) {
                $this->objects[$uid] = new VCalendar();
            }

            $this->objects[$uid]->add(clone $component);
        }
    }

    
    public function getNext()
    {
        if ($object = array_shift($this->objects)) {
                        $object->version = '2.0';
            $object->prodid = '-//Sabre//Sabre VObject '.VObject\Version::VERSION.'//EN';
            $object->calscale = 'GREGORIAN';

                        foreach ($this->vtimezones as $vtimezone) {
                $object->add($vtimezone);
            }

            return $object;
        } else {
            return;
        }
    }
}
