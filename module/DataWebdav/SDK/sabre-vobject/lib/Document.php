<?php

namespace Sabre\VObject;


abstract class Document extends Component
{
    
    const UNKNOWN = 1;

    
    const VCALENDAR10 = 2;

    
    const ICALENDAR20 = 3;

    
    const VCARD21 = 4;

    
    const VCARD30 = 5;

    
    const VCARD40 = 6;

    
    public static $defaultName;

    
    public static $propertyMap = [];

    
    public static $componentMap = [];

    
    public static $valueMap = [];

    
    public function __construct()
    {
        $args = func_get_args();
        $name = static::$defaultName;
        if (0 === count($args) || is_array($args[0])) {
            $children = isset($args[0]) ? $args[0] : [];
            $defaults = isset($args[1]) ? $args[1] : true;
        } else {
            $name = $args[0];
            $children = isset($args[1]) ? $args[1] : [];
            $defaults = isset($args[2]) ? $args[2] : true;
        }
        parent::__construct($this, $name, $children, $defaults);
    }

    
    public function getDocumentType()
    {
        return self::UNKNOWN;
    }

    
    public function create($name)
    {
        if (isset(static::$componentMap[strtoupper($name)])) {
            return call_user_func_array([$this, 'createComponent'], func_get_args());
        } else {
            return call_user_func_array([$this, 'createProperty'], func_get_args());
        }
    }

    
    public function createComponent($name, array $children = null, $defaults = true)
    {
        $name = strtoupper($name);
        $class = 'Sabre\\VObject\\Component';

        if (isset(static::$componentMap[$name])) {
            $class = static::$componentMap[$name];
        }
        if (is_null($children)) {
            $children = [];
        }

        return new $class($this, $name, $children, $defaults);
    }

    
    public function createProperty($name, $value = null, array $parameters = null, $valueType = null)
    {
                if (false !== ($i = strpos($name, '.'))) {
            $group = substr($name, 0, $i);
            $name = strtoupper(substr($name, $i + 1));
        } else {
            $name = strtoupper($name);
            $group = null;
        }

        $class = null;

        if ($valueType) {
                                    $class = $this->getClassNameForPropertyValue($valueType);
        }

        if (is_null($class)) {
                        if (isset($parameters['VALUE'])) {
                $class = $this->getClassNameForPropertyValue($parameters['VALUE']);
                if (is_null($class)) {
                    throw new InvalidDataException('Unsupported VALUE parameter for '.$name.' property. You supplied "'.$parameters['VALUE'].'"');
                }
            } else {
                $class = $this->getClassNameForPropertyName($name);
            }
        }
        if (is_null($parameters)) {
            $parameters = [];
        }

        return new $class($this, $name, $value, $parameters, $group);
    }

    
    public function getClassNameForPropertyValue($valueParam)
    {
        $valueParam = strtoupper($valueParam);
        if (isset(static::$valueMap[$valueParam])) {
            return static::$valueMap[$valueParam];
        }
    }

    
    public function getClassNameForPropertyName($propertyName)
    {
        if (isset(static::$propertyMap[$propertyName])) {
            return static::$propertyMap[$propertyName];
        } else {
            return 'Sabre\\VObject\\Property\\Unknown';
        }
    }
}
