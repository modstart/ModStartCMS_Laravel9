<?php

namespace Sabre\VObject;

use Sabre\Xml;


abstract class Property extends Node
{
    
    public $name;

    
    public $group;

    
    public $parameters = [];

    
    protected $value;

    
    public $delimiter = ';';

    
    public function __construct(Component $root, $name, $value = null, array $parameters = [], $group = null)
    {
        $this->name = $name;
        $this->group = $group;

        $this->root = $root;

        foreach ($parameters as $k => $v) {
            $this->add($k, $v);
        }

        if (!is_null($value)) {
            $this->setValue($value);
        }
    }

    
    public function setValue($value)
    {
        $this->value = $value;
    }

    
    public function getValue()
    {
        if (is_array($this->value)) {
            if (0 == count($this->value)) {
                return;
            } elseif (1 === count($this->value)) {
                return $this->value[0];
            } else {
                return $this->getRawMimeDirValue();
            }
        } else {
            return $this->value;
        }
    }

    
    public function setParts(array $parts)
    {
        $this->value = $parts;
    }

    
    public function getParts()
    {
        if (is_null($this->value)) {
            return [];
        } elseif (is_array($this->value)) {
            return $this->value;
        } else {
            return [$this->value];
        }
    }

    
    public function add($name, $value = null)
    {
        $noName = false;
        if (null === $name) {
            $name = Parameter::guessParameterNameByValue($value);
            $noName = true;
        }

        if (isset($this->parameters[strtoupper($name)])) {
            $this->parameters[strtoupper($name)]->addValue($value);
        } else {
            $param = new Parameter($this->root, $name, $value);
            $param->noName = $noName;
            $this->parameters[$param->name] = $param;
        }
    }

    
    public function parameters()
    {
        return $this->parameters;
    }

    
    abstract public function getValueType();

    
    abstract public function setRawMimeDirValue($val);

    
    abstract public function getRawMimeDirValue();

    
    public function serialize()
    {
        $str = $this->name;
        if ($this->group) {
            $str = $this->group.'.'.$this->name;
        }

        foreach ($this->parameters() as $param) {
            $str .= ';'.$param->serialize();
        }

        $str .= ':'.$this->getRawMimeDirValue();

        $str = \preg_replace(
            '/(
                (?:^.)?         # 1 additional byte in first line because of missing single space (see next line)
                .{1,74}         # max 75 bytes per line (1 byte is used for a single space added after every CRLF)
                (?![\x80-\xbf]) # prevent splitting multibyte characters
            )/x',
            "$1\r\n ",
            $str
        );

                return \substr($str, 0, -1);
    }

    
    public function getJsonValue()
    {
        return $this->getParts();
    }

    
    public function setJsonValue(array $value)
    {
        if (1 === count($value)) {
            $this->setValue(reset($value));
        } else {
            $this->setValue($value);
        }
    }

    
    public function jsonSerialize()
    {
        $parameters = [];

        foreach ($this->parameters as $parameter) {
            if ('VALUE' === $parameter->name) {
                continue;
            }
            $parameters[strtolower($parameter->name)] = $parameter->jsonSerialize();
        }
                        if ($this->group) {
            $parameters['group'] = $this->group;
        }

        return array_merge(
            [
                strtolower($this->name),
                (object) $parameters,
                strtolower($this->getValueType()),
            ],
            $this->getJsonValue()
        );
    }

    
    public function setXmlValue(array $value)
    {
        $this->setJsonValue($value);
    }

    
    public function xmlSerialize(Xml\Writer $writer)
    {
        $parameters = [];

        foreach ($this->parameters as $parameter) {
            if ('VALUE' === $parameter->name) {
                continue;
            }

            $parameters[] = $parameter;
        }

        $writer->startElement(strtolower($this->name));

        if (!empty($parameters)) {
            $writer->startElement('parameters');

            foreach ($parameters as $parameter) {
                $writer->startElement(strtolower($parameter->name));
                $writer->write($parameter);
                $writer->endElement();
            }

            $writer->endElement();
        }

        $this->xmlSerializeValue($writer);
        $writer->endElement();
    }

    
    protected function xmlSerializeValue(Xml\Writer $writer)
    {
        $valueType = strtolower($this->getValueType());

        foreach ($this->getJsonValue() as $values) {
            foreach ((array) $values as $value) {
                $writer->writeElement($valueType, $value);
            }
        }
    }

    
    public function __toString()
    {
        return (string) $this->getValue();
    }

    

    
    public function offsetExists($name)
    {
        if (is_int($name)) {
            return parent::offsetExists($name);
        }

        $name = strtoupper($name);

        foreach ($this->parameters as $parameter) {
            if ($parameter->name == $name) {
                return true;
            }
        }

        return false;
    }

    
    public function offsetGet($name)
    {
        if (is_int($name)) {
            return parent::offsetGet($name);
        }
        $name = strtoupper($name);

        if (!isset($this->parameters[$name])) {
            return;
        }

        return $this->parameters[$name];
    }

    
    public function offsetSet($name, $value)
    {
        if (is_int($name)) {
            parent::offsetSet($name, $value);
                                                return;
                    }

        $param = new Parameter($this->root, $name, $value);
        $this->parameters[$param->name] = $param;
    }

    
    public function offsetUnset($name)
    {
        if (is_int($name)) {
            parent::offsetUnset($name);
                                                return;
                    }

        unset($this->parameters[strtoupper($name)]);
    }

    

    
    public function __clone()
    {
        foreach ($this->parameters as $key => $child) {
            $this->parameters[$key] = clone $child;
            $this->parameters[$key]->parent = $this;
        }
    }

    
    public function validate($options = 0)
    {
        $warnings = [];

                if (!StringUtil::isUTF8($this->getRawMimeDirValue())) {
            $oldValue = $this->getRawMimeDirValue();
            $level = 3;
            if ($options & self::REPAIR) {
                $newValue = StringUtil::convertToUTF8($oldValue);
                if (true || StringUtil::isUTF8($newValue)) {
                    $this->setRawMimeDirValue($newValue);
                    $level = 1;
                }
            }

            if (preg_match('%([\x00-\x08\x0B-\x0C\x0E-\x1F\x7F])%', $oldValue, $matches)) {
                $message = 'Property contained a control character (0x'.bin2hex($matches[1]).')';
            } else {
                $message = 'Property is not valid UTF-8! '.$oldValue;
            }

            $warnings[] = [
                'level' => $level,
                'message' => $message,
                'node' => $this,
            ];
        }

                if (!preg_match('/^([A-Z0-9-]+)$/', $this->name)) {
            $warnings[] = [
                'level' => $options & self::REPAIR ? 1 : 3,
                'message' => 'The propertyname: '.$this->name.' contains invalid characters. Only A-Z, 0-9 and - are allowed',
                'node' => $this,
            ];
            if ($options & self::REPAIR) {
                                $this->name = strtoupper(
                    str_replace('_', '-', $this->name)
                );
                                $this->name = preg_replace('/([^A-Z0-9-])/u', '', $this->name);
            }
        }

        if ($encoding = $this->offsetGet('ENCODING')) {
            if (Document::VCARD40 === $this->root->getDocumentType()) {
                $warnings[] = [
                    'level' => 3,
                    'message' => 'ENCODING parameter is not valid in vCard 4.',
                    'node' => $this,
                ];
            } else {
                $encoding = (string) $encoding;

                $allowedEncoding = [];

                switch ($this->root->getDocumentType()) {
                    case Document::ICALENDAR20:
                        $allowedEncoding = ['8BIT', 'BASE64'];
                        break;
                    case Document::VCARD21:
                        $allowedEncoding = ['QUOTED-PRINTABLE', 'BASE64', '8BIT'];
                        break;
                    case Document::VCARD30:
                        $allowedEncoding = ['B'];
                                                if ($options & self::REPAIR) {
                            if ('BASE64' === strtoupper($encoding)) {
                                $encoding = 'B';
                                $this['ENCODING'] = $encoding;
                                $warnings[] = [
                                    'level' => 1,
                                    'message' => 'ENCODING=BASE64 has been transformed to ENCODING=B.',
                                    'node' => $this,
                                ];
                            }
                        }
                        break;
                }
                if ($allowedEncoding && !in_array(strtoupper($encoding), $allowedEncoding)) {
                    $warnings[] = [
                        'level' => 3,
                        'message' => 'ENCODING='.strtoupper($encoding).' is not valid for this document type.',
                        'node' => $this,
                    ];
                }
            }
        }

                foreach ($this->parameters as $param) {
            $warnings = array_merge($warnings, $param->validate($options));
        }

        return $warnings;
    }

    
    public function destroy()
    {
        parent::destroy();
        foreach ($this->parameters as $param) {
            $param->destroy();
        }
        $this->parameters = [];
    }
}
