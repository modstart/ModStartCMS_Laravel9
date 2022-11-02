<?php

namespace Sabre\VObject\Component;

use DateTimeInterface;
use Sabre\VObject;


class VFreeBusy extends VObject\Component
{
    
    public function isFree(DateTimeInterface $start, DatetimeInterface $end)
    {
        foreach ($this->select('FREEBUSY') as $freebusy) {
                                    if (isset($freebusy['FBTYPE']) && 'BUSY' !== strtoupper(substr((string) $freebusy['FBTYPE'], 0, 4))) {
                continue;
            }

                                    $periods = explode(',', (string) $freebusy);

            foreach ($periods as $period) {
                                                                list($busyStart, $busyEnd) = explode('/', $period);

                $busyStart = VObject\DateTimeParser::parse($busyStart);
                $busyEnd = VObject\DateTimeParser::parse($busyEnd);
                if ($busyEnd instanceof \DateInterval) {
                    $busyEnd = $busyStart->add($busyEnd);
                }

                if ($start < $busyEnd && $end > $busyStart) {
                    return false;
                }
            }
        }

        return true;
    }

    
    public function getValidationRules()
    {
        return [
            'UID' => 1,
            'DTSTAMP' => 1,

            'CONTACT' => '?',
            'DTSTART' => '?',
            'DTEND' => '?',
            'ORGANIZER' => '?',
            'URL' => '?',

            'ATTENDEE' => '*',
            'COMMENT' => '*',
            'FREEBUSY' => '*',
            'REQUEST-STATUS' => '*',
        ];
    }
}
