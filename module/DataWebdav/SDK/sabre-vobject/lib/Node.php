<?php

namespace Sabre\VObject;

use Sabre\Xml;


abstract class Node implements \IteratorAggregate, \ArrayAccess, \Countable, \JsonSerializable, Xml\XmlSerializable
{
    
    const REPAIR = 1;

    
    const PROFILE_CARDDAV = 2;

    
    const PROFILE_CALDAV = 4;

    
    public $parent;

    
    protected $iterator = null;

    
    protected $root;

    
    abstract public function serialize();

    
    abstract public function jsonSerialize();

    
    abstract public function xmlSerialize(Xml\Writer $writer);

    
    public function destroy()
    {
        $this->parent = null;
        $this->root = null;
    }

    

    
    public function getIterator()
    {
        if (!is_null($this->iterator)) {
            return $this->iterator;
        }

        return new ElementList([$this]);
    }

    
    public function setIterator(ElementList $iterator)
    {
        $this->iterator = $iterator;
    }

    
    public function validate($options = 0)
    {
        return [];
    }

    

    

    
    public function count()
    {
        $it = $this->getIterator();

        return $it->count();
    }

    

    

    
    public function offsetExists($offset)
    {
        $iterator = $this->getIterator();

        return $iterator->offsetExists($offset);
    }

    
    public function offsetGet($offset)
    {
        $iterator = $this->getIterator();

        return $iterator->offsetGet($offset);
    }

    
    public function offsetSet($offset, $value)
    {
        $iterator = $this->getIterator();
        $iterator->offsetSet($offset, $value);

                        }

    
    
    public function offsetUnset($offset)
    {
        $iterator = $this->getIterator();
        $iterator->offsetUnset($offset);

                        }

    
    
}
