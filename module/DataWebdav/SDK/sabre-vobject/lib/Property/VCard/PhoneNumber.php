<?php

namespace Sabre\VObject\Property\VCard;

use Sabre\VObject\Property;


class PhoneNumber extends Property\Text
{
    protected $structuredValues = [];

    
    public function getValueType()
    {
        return 'PHONE-NUMBER';
    }
}
