<?php

namespace Sabre\VObject;

use ArrayIterator;
use LogicException;


class ElementList extends ArrayIterator
{
    

    
    public function offsetSet($offset, $value)
    {
        throw new LogicException('You can not add new objects to an ElementList');
    }

    
    public function offsetUnset($offset)
    {
        throw new LogicException('You can not remove objects from an ElementList');
    }

    
}
