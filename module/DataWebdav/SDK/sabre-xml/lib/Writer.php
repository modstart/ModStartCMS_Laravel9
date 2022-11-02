<?php

namespace Sabre\Xml;

use XMLWriter;


class Writer extends XMLWriter {

    use ContextStackTrait;

    
    protected $adhocNamespaces = [];

    
    protected $namespacesWritten = false;

    
    function write($value) {

        Serializer\standardSerializer($this, $value);

    }

    
    function startElement($name) {

        if ($name[0] === '{') {

            list($namespace, $localName) =
                Service::parseClarkNotation($name);

            if (array_key_exists($namespace, $this->namespaceMap)) {
                $result = $this->startElementNS(
                    $this->namespaceMap[$namespace] === '' ? null : $this->namespaceMap[$namespace],
                    $localName,
                    null
                );
            } else {

                                                if ($namespace === "" || $namespace === null) {
                    $result = $this->startElement($localName);
                    $this->writeAttribute('xmlns', '');
                } else {
                    if (!isset($this->adhocNamespaces[$namespace])) {
                        $this->adhocNamespaces[$namespace] = 'x' . (count($this->adhocNamespaces) + 1);
                    }
                    $result = $this->startElementNS($this->adhocNamespaces[$namespace], $localName, $namespace);
                }
            }

        } else {
            $result = parent::startElement($name);
        }

        if (!$this->namespacesWritten) {

            foreach ($this->namespaceMap as $namespace => $prefix) {
                $this->writeAttribute(($prefix ? 'xmlns:' . $prefix : 'xmlns'), $namespace);
            }
            $this->namespacesWritten = true;

        }

        return $result;

    }

    
    function writeElement($name, $content = null) {

        $this->startElement($name);
        if (!is_null($content)) {
            $this->write($content);
        }
        $this->endElement();

    }

    
    function writeAttributes(array $attributes) {

        foreach ($attributes as $name => $value) {
            $this->writeAttribute($name, $value);
        }

    }

    
    function writeAttribute($name, $value) {

        if ($name[0] === '{') {

            list(
                $namespace,
                $localName
            ) = Service::parseClarkNotation($name);

            if (array_key_exists($namespace, $this->namespaceMap)) {
                                $this->writeAttribute(
                    $this->namespaceMap[$namespace] . ':' . $localName,
                    $value
                );
            } else {

                                if (!isset($this->adhocNamespaces[$namespace])) {
                    $this->adhocNamespaces[$namespace] = 'x' . (count($this->adhocNamespaces) + 1);
                }
                $this->writeAttributeNS(
                    $this->adhocNamespaces[$namespace],
                    $localName,
                    $namespace,
                    $value
                );

            }

        } else {
            return parent::writeAttribute($name, $value);
        }

    }

}
