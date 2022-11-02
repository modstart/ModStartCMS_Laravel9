<?php

namespace Sabre\VObject\Property\VCard;


class DateTime extends DateAndOrTime
{
    
    public function getValueType()
    {
        return 'DATE-TIME';
    }
}
