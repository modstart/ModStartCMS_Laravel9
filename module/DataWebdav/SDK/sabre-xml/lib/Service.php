<?php

namespace Sabre\Xml;


class Service {

    
    public $elementMap = [];

    
    public $namespaceMap = [];

    
    public $classMap = [];

    
    function getReader() {

        $r = new Reader();
        $r->elementMap = $this->elementMap;
        return $r;

    }

    
    function getWriter() {

        $w = new Writer();
        $w->namespaceMap = $this->namespaceMap;
        $w->classMap = $this->classMap;
        return $w;

    }

    
    function parse($input, $contextUri = null, &$rootElementName = null) {

        if (is_resource($input)) {
                                    $input = stream_get_contents($input);
        }
        $r = $this->getReader();
        $r->contextUri = $contextUri;
        $r->xml($input);

        $result = $r->parse();
        $rootElementName = $result['name'];
        return $result['value'];

    }

    
    function expect($rootElementName, $input, $contextUri = null) {

        if (is_resource($input)) {
                                    $input = stream_get_contents($input);
        }
        $r = $this->getReader();
        $r->contextUri = $contextUri;
        $r->xml($input);

        $rootElementName = (array)$rootElementName;

        foreach ($rootElementName as &$rEl) {
            if ($rEl[0] !== '{') $rEl = '{}' . $rEl;
        }

        $result = $r->parse();
        if (!in_array($result['name'], $rootElementName, true)) {
            throw new ParseException('Expected ' . implode(' or ', (array)$rootElementName) . ' but received ' . $result['name'] . ' as the root element');
        }
        return $result['value'];

    }

    
    function write($rootElementName, $value, $contextUri = null) {

        $w = $this->getWriter();
        $w->openMemory();
        $w->contextUri = $contextUri;
        $w->setIndent(true);
        $w->startDocument();
        $w->writeElement($rootElementName, $value);
        return $w->outputMemory();

    }

    
    function mapValueObject($elementName, $className) {
        list($namespace) = self::parseClarkNotation($elementName);

        $this->elementMap[$elementName] = function(Reader $reader) use ($className, $namespace) {
            return \Sabre\Xml\Deserializer\valueObject($reader, $className, $namespace);
        };
        $this->classMap[$className] = function(Writer $writer, $valueObject) use ($namespace) {
            return \Sabre\Xml\Serializer\valueObject($writer, $valueObject, $namespace);
        };
        $this->valueObjectMap[$className] = $elementName;
    }

    
    function writeValueObject($object, $contextUri = null) {

        if (!isset($this->valueObjectMap[get_class($object)])) {
            throw new \InvalidArgumentException('"' . get_class($object) . '" is not a registered value object class. Register your class with mapValueObject.');
        }
        return $this->write(
            $this->valueObjectMap[get_class($object)],
            $object,
            $contextUri
        );

    }

    
    static function parseClarkNotation($str) {
        static $cache = [];

        if (!isset($cache[$str])) {

            if (!preg_match('/^{([^}]*)}(.*)$/', $str, $matches)) {
                throw new \InvalidArgumentException('\'' . $str . '\' is not a valid clark-notation formatted string');
            }

            $cache[$str] = [
                $matches[1],
                $matches[2]
            ];
        }

        return $cache[$str];
    }

    
    protected $valueObjectMap = [];

}
