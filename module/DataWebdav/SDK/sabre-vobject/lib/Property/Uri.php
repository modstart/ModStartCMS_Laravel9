<?php

namespace Sabre\VObject\Property;

use Sabre\VObject\Parameter;
use Sabre\VObject\Property;


class Uri extends Text
{
    
    public $delimiter = null;

    
    public function getValueType()
    {
        return 'URI';
    }

    
    public function parameters()
    {
        $parameters = parent::parameters();
        if (!isset($parameters['VALUE']) && in_array($this->name, ['URL', 'PHOTO'])) {
                                                                                                            $parameters['VALUE'] = new Parameter($this->root, 'VALUE', 'URI');
        }

        return $parameters;
    }

    
    public function setRawMimeDirValue($val)
    {
                                                                        if ('URL' === $this->name) {
            $regex = '#  (?: (\\\\ (?: \\\\ | : ) ) ) #x';
            $matches = preg_split($regex, $val, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            $newVal = '';
            foreach ($matches as $match) {
                switch ($match) {
                    case '\:':
                        $newVal .= ':';
                        break;
                    default:
                        $newVal .= $match;
                        break;
                }
            }
            $this->value = $newVal;
        } else {
            $this->value = strtr($val, ['\,' => ',']);
        }
    }

    
    public function getRawMimeDirValue()
    {
        if (is_array($this->value)) {
            $value = $this->value[0];
        } else {
            $value = $this->value;
        }

        return strtr($value, [',' => '\,']);
    }
}
