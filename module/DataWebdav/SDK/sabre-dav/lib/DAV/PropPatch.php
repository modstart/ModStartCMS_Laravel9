<?php

namespace Sabre\DAV;

use UnexpectedValueException;


class PropPatch {

    
    protected $mutations;

    
    protected $result = [];

    
    protected $propertyUpdateCallbacks = [];

    
    protected $failed = false;

    
    function __construct(array $mutations) {

        $this->mutations = $mutations;

    }

    
    function handle($properties, callable $callback) {

        $usedProperties = [];
        foreach ((array)$properties as $propertyName) {

            if (array_key_exists($propertyName, $this->mutations) && !isset($this->result[$propertyName])) {

                $usedProperties[] = $propertyName;
                                $this->result[$propertyName] = 202;
            }

        }

                if (!$usedProperties) {
            return;
        }
        $this->propertyUpdateCallbacks[] = [
                                                is_string($properties) ? $properties : $usedProperties,
            $callback
        ];

    }

    
    function handleRemaining(callable $callback) {

        $properties = $this->getRemainingMutations();
        if (!$properties) {
                        return;
        }

        foreach ($properties as $propertyName) {
                        $this->result[$propertyName] = 202;

            $this->propertyUpdateCallbacks[] = [
                $properties,
                $callback
            ];
        }

    }

    
    function setResultCode($properties, $resultCode) {

        foreach ((array)$properties as $propertyName) {
            $this->result[$propertyName] = $resultCode;
        }

        if ($resultCode >= 400) {
            $this->failed = true;
        }

    }

    
    function setRemainingResultCode($resultCode) {

        $this->setResultCode(
            $this->getRemainingMutations(),
            $resultCode
        );

    }

    
    function getRemainingMutations() {

        $remaining = [];
        foreach ($this->mutations as $propertyName => $propValue) {
            if (!isset($this->result[$propertyName])) {
                $remaining[] = $propertyName;
            }
        }

        return $remaining;

    }

    
    function getRemainingValues() {

        $remaining = [];
        foreach ($this->mutations as $propertyName => $propValue) {
            if (!isset($this->result[$propertyName])) {
                $remaining[$propertyName] = $propValue;
            }
        }

        return $remaining;

    }

    
    function commit() {

                foreach ($this->mutations as $propertyName => $value) {

            if (!isset($this->result[$propertyName])) {
                $this->failed = true;
                $this->result[$propertyName] = 403;
            }

        }

        foreach ($this->propertyUpdateCallbacks as $callbackInfo) {

            if ($this->failed) {
                break;
            }
            if (is_string($callbackInfo[0])) {
                $this->doCallbackSingleProp($callbackInfo[0], $callbackInfo[1]);
            } else {
                $this->doCallbackMultiProp($callbackInfo[0], $callbackInfo[1]);
            }

        }

        
        if ($this->failed) {

            foreach ($this->result as $propertyName => $status) {
                if ($status === 202) {
                                        $this->result[$propertyName] = 424;
                }
            }

        }

        return !$this->failed;

    }

    
    private function doCallBackSingleProp($propertyName, callable $callback) {

        $result = $callback($this->mutations[$propertyName]);
        if (is_bool($result)) {
            if ($result) {
                if (is_null($this->mutations[$propertyName])) {
                                        $result = 204;
                } else {
                                        $result = 200;
                }
            } else {
                                $result = 403;
            }
        }
        if (!is_int($result)) {
            throw new UnexpectedValueException('A callback sent to handle() did not return an int or a bool');
        }
        $this->result[$propertyName] = $result;
        if ($result >= 400) {
            $this->failed = true;
        }

    }

    
    private function doCallBackMultiProp(array $propertyList, callable $callback) {

        $argument = [];
        foreach ($propertyList as $propertyName) {
            $argument[$propertyName] = $this->mutations[$propertyName];
        }

        $result = $callback($argument);

        if (is_array($result)) {
            foreach ($propertyList as $propertyName) {
                if (!isset($result[$propertyName])) {
                    $resultCode = 500;
                } else {
                    $resultCode = $result[$propertyName];
                }
                if ($resultCode >= 400) {
                    $this->failed = true;
                }
                $this->result[$propertyName] = $resultCode;

            }
        } elseif ($result === true) {

                        foreach ($argument as $propertyName => $propertyValue) {
                $this->result[$propertyName] = is_null($propertyValue) ? 204 : 200;
            }

        } elseif ($result === false) {
                        $this->failed = true;
            foreach ($propertyList as $propertyName) {
                $this->result[$propertyName] = 403;
            }
        } else {
            throw new UnexpectedValueException('A callback sent to handle() did not return an array or a bool');
        }

    }

    
    function getResult() {

        return $this->result;

    }

    
    function getMutations() {

        return $this->mutations;

    }

}
