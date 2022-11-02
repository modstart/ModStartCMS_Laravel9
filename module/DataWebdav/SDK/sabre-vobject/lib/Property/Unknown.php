<?php

namespace Sabre\VObject\Property;


class Unknown extends Text
{
    
    public function getJsonValue()
    {
        return [$this->getRawMimeDirValue()];
    }

    
    public function getValueType()
    {
        return 'UNKNOWN';
    }
}
