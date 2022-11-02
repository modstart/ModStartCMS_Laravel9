<?php

namespace Sabre\VObject\Component;

use Sabre\VObject;


class VTimeZone extends VObject\Component
{
    
    public function getTimeZone()
    {
        return VObject\TimeZoneUtil::getTimeZone((string) $this->TZID, $this->root);
    }

    
    public function getValidationRules()
    {
        return [
            'TZID' => 1,

            'LAST-MODIFIED' => '?',
            'TZURL' => '?',

                                                            'STANDARD' => '*',
            'DAYLIGHT' => '*',
        ];
    }
}
