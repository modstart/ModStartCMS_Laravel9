<?php

namespace Sabre\VObject\Property\VCard;


class Date extends DateAndOrTime
{
    
    public function getValueType()
    {
        return 'DATE';
    }

    
    public function setDateTime(\DateTimeInterface $dt)
    {
        $this->value = $dt->format('Ymd');
    }
}
