<?php

namespace Sabre\VObject\Property\VCard;

use
    Sabre\VObject\Property;


class LanguageTag extends Property
{
    
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
        return 'LANGUAGE-TAG';
    }
}
