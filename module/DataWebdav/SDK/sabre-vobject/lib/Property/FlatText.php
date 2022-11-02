<?php

namespace Sabre\VObject\Property;


class FlatText extends Text
{
    
    public $delimiter = ',';

    
    public function setQuotedPrintableValue($val)
    {
        $val = quoted_printable_decode($val);
        $this->setValue($val);
    }
}
