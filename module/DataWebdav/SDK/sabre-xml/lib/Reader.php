<?php

namespace Sabre\Xml;

use XMLReader;


class Reader extends XMLReader {

    use ContextStackTrait;

    
    function getClark() {

        if (! $this->localName) {
            return null;
        }

        return '{' . $this->namespaceURI . '}' . $this->localName;

    }

    
    function parse() {

        $previousEntityState = libxml_disable_entity_loader(true);
        $previousSetting = libxml_use_internal_errors(true);

        try {

                                                            while ($this->nodeType !== self::ELEMENT && @$this->read()) {
                            }
            $result = $this->parseCurrentElement();

            $errors = libxml_get_errors();
            libxml_clear_errors();
            if ($errors) {
                throw new LibXMLException($errors);
            }

        } finally {
            libxml_use_internal_errors($previousSetting);
            libxml_disable_entity_loader($previousEntityState);
        }

        return $result;
    }



    
    function parseGetElements(array $elementMap = null) {

        $result = $this->parseInnerTree($elementMap);
        if (!is_array($result)) {
            return [];
        }
        return $result;

    }

    
    function parseInnerTree(array $elementMap = null) {

        $text = null;
        $elements = [];

        if ($this->nodeType === self::ELEMENT && $this->isEmptyElement) {
                        $this->next();
            return null;
        }

        if (!is_null($elementMap)) {
            $this->pushContext();
            $this->elementMap = $elementMap;
        }

        try {

                                                            if (!@$this->read()) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                if ($errors) {
                    throw new LibXMLException($errors);
                }
                throw new ParseException('This should never happen (famous last words)');
            }

            while (true) {

                if (!$this->isValid()) {

                    $errors = libxml_get_errors();

                    if ($errors) {
                        libxml_clear_errors();
                        throw new LibXMLException($errors);
                    }
                }

                switch ($this->nodeType) {
                    case self::ELEMENT :
                        $elements[] = $this->parseCurrentElement();
                        break;
                    case self::TEXT :
                    case self::CDATA :
                        $text .= $this->value;
                        $this->read();
                        break;
                    case self::END_ELEMENT :
                                                $this->read();
                        break 2;
                    case self::NONE :
                        throw new ParseException('We hit the end of the document prematurely. This likely means that some parser "eats" too many elements. Do not attempt to continue parsing.');
                    default :
                                                $this->read();
                        break;
                }

            }

        } finally {

            if (!is_null($elementMap)) {
                $this->popContext();
            }

        }
        return ($elements ? $elements : $text);

    }

    
    function readText() {

        $result = '';
        $previousDepth = $this->depth;

        while ($this->read() && $this->depth != $previousDepth) {
            if (in_array($this->nodeType, [XMLReader::TEXT, XMLReader::CDATA, XMLReader::WHITESPACE])) {
                $result .= $this->value;
            }
        }
        return $result;

    }

    
    function parseCurrentElement() {

        $name = $this->getClark();

        $attributes = [];

        if ($this->hasAttributes) {
            $attributes = $this->parseAttributes();
        }

        $value = call_user_func(
            $this->getDeserializerForElementName($name),
            $this
        );

        return [
            'name'       => $name,
            'value'      => $value,
            'attributes' => $attributes,
        ];
    }


    
    function parseAttributes() {

        $attributes = [];

        while ($this->moveToNextAttribute()) {
            if ($this->namespaceURI) {

                                if ($this->namespaceURI === 'http://www.w3.org/2000/xmlns/') {
                    continue;
                }

                $name = $this->getClark();
                $attributes[$name] = $this->value;

            } else {
                $attributes[$this->localName] = $this->value;
            }
        }
        $this->moveToElement();

        return $attributes;

    }

    
    function getDeserializerForElementName($name) {


        if (!array_key_exists($name, $this->elementMap)) {
            if (substr($name, 0, 2) == '{}' && array_key_exists(substr($name, 2), $this->elementMap)) {
                $name = substr($name, 2);
            } else {
                return ['Sabre\\Xml\\Element\\Base', 'xmlDeserialize'];
            }
        }

        $deserializer = $this->elementMap[$name];
        if (is_subclass_of($deserializer, 'Sabre\\Xml\\XmlDeserializable')) {
            return [$deserializer, 'xmlDeserialize'];
        }

        if (is_callable($deserializer)) {
            return $deserializer;
        }

        $type = gettype($deserializer);
        if ($type === 'string') {
            $type .= ' (' . $deserializer . ')';
        } elseif ($type === 'object') {
            $type .= ' (' . get_class($deserializer) . ')';
        }
        throw new \LogicException('Could not use this type as a deserializer: ' . $type . ' for element: ' . $name);

    }

}
