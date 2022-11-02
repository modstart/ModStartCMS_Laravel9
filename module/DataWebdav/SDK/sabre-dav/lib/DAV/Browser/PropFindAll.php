<?php

namespace Sabre\DAV\Browser;

use Sabre\DAV\PropFind;


class PropFindAll extends PropFind {

    
    function __construct($path) {

        parent::__construct($path, []);

    }

    
    function handle($propertyName, $valueOrCallBack) {

        if (is_callable($valueOrCallBack)) {
            $value = $valueOrCallBack();
        } else {
            $value = $valueOrCallBack;
        }
        if (!is_null($value)) {
            $this->result[$propertyName] = [200, $value];
        }

    }

    
    function set($propertyName, $value, $status = null) {

        if (is_null($status)) {
            $status = is_null($value) ? 404 : 200;
        }
        $this->result[$propertyName] = [$status, $value];

    }

    
    function get($propertyName) {

        return isset($this->result[$propertyName]) ? $this->result[$propertyName][1] : null;

    }

    
    function getStatus($propertyName) {

        return isset($this->result[$propertyName]) ? $this->result[$propertyName][0] : 404;

    }

    
    function get404Properties() {

        $result = [];
        foreach ($this->result as $propertyName => $stuff) {
            if ($stuff[0] === 404) {
                $result[] = $propertyName;
            }
        }
                if (!$result) {
            $result[] = '{http://sabredav.org/ns}idk';
        }
        return $result;

    }

}
