<?php

namespace Sabre\DAV;


class PropFind {

    
    const NORMAL = 0;

    
    const ALLPROPS = 1;

    
    const PROPNAME = 2;

    
    function __construct($path, array $properties, $depth = 0, $requestType = self::NORMAL) {

        $this->path = $path;
        $this->properties = $properties;
        $this->depth = $depth;
        $this->requestType = $requestType;

        if ($requestType === self::ALLPROPS) {
            $this->properties = [
                '{DAV:}getlastmodified',
                '{DAV:}getcontentlength',
                '{DAV:}resourcetype',
                '{DAV:}quota-used-bytes',
                '{DAV:}quota-available-bytes',
                '{DAV:}getetag',
                '{DAV:}getcontenttype',
            ];
        }

        foreach ($this->properties as $propertyName) {

                        $this->result[$propertyName] = [404, null];

        }
        $this->itemsLeft = count($this->result);

    }

    
    function handle($propertyName, $valueOrCallBack) {

        if ($this->itemsLeft && isset($this->result[$propertyName]) && $this->result[$propertyName][0] === 404) {
            if (is_callable($valueOrCallBack)) {
                $value = $valueOrCallBack();
            } else {
                $value = $valueOrCallBack;
            }
            if (!is_null($value)) {
                $this->itemsLeft--;
                $this->result[$propertyName] = [200, $value];
            }
        }

    }

    
    function set($propertyName, $value, $status = null) {

        if (is_null($status)) {
            $status = is_null($value) ? 404 : 200;
        }
                        if (!isset($this->result[$propertyName])) {
            if ($this->requestType === self::ALLPROPS) {
                $this->result[$propertyName] = [$status, $value];
            }
            return;
        }
        if ($status !== 404 && $this->result[$propertyName][0] === 404) {
            $this->itemsLeft--;
        } elseif ($status === 404 && $this->result[$propertyName][0] !== 404) {
            $this->itemsLeft++;
        }
        $this->result[$propertyName] = [$status, $value];

    }

    
    function get($propertyName) {

        return isset($this->result[$propertyName]) ? $this->result[$propertyName][1] : null;

    }

    
    function getStatus($propertyName) {

        return isset($this->result[$propertyName]) ? $this->result[$propertyName][0] : null;

    }

    
    function setPath($path) {

        $this->path = $path;

    }

    
    function getPath() {

        return $this->path;

    }

    
    function getDepth() {

        return $this->depth;

    }

    
    function setDepth($depth) {

        $this->depth = $depth;

    }

    
    function get404Properties() {

        if ($this->itemsLeft === 0) {
            return [];
        }
        $result = [];
        foreach ($this->result as $propertyName => $stuff) {
            if ($stuff[0] === 404) {
                $result[] = $propertyName;
            }
        }
        return $result;

    }

    
    function getRequestedProperties() {

        return $this->properties;

    }

    
    function isAllProps() {

        return $this->requestType === self::ALLPROPS;

    }

    
    function getResultForMultiStatus() {

        $r = [
            200 => [],
            404 => [],
        ];
        foreach ($this->result as $propertyName => $info) {
            if (!isset($r[$info[0]])) {
                $r[$info[0]] = [$propertyName => $info[1]];
            } else {
                $r[$info[0]][$propertyName] = $info[1];
            }
        }
                if ($this->requestType === self::ALLPROPS) unset($r[404]);
        return $r;

    }

    
    protected $path;

    
    protected $depth = 0;

    
    protected $requestType;

    
    protected $properties = [];

    
    protected $result = [];

    
    protected $itemsLeft;

}
