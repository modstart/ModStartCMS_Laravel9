<?php

namespace Sabre\HTTP;


trait MessageDecoratorTrait {

    
    protected $inner;

    
    function getBodyAsStream() {

        return $this->inner->getBodyAsStream();

    }

    
    function getBodyAsString() {

        return $this->inner->getBodyAsString();

    }

    
    function getBody() {

        return $this->inner->getBody();

    }

    
    function setBody($body) {

        $this->inner->setBody($body);

    }

    
    function getHeaders() {

        return $this->inner->getHeaders();

    }

    
    function hasHeader($name) {

        return $this->inner->hasHeader($name);

    }

    
    function getHeader($name) {

        return $this->inner->getHeader($name);

    }

    
    function getHeaderAsArray($name) {

        return $this->inner->getHeaderAsArray($name);

    }

    
    function setHeader($name, $value) {

        $this->inner->setHeader($name, $value);

    }

    
    function setHeaders(array $headers) {

        $this->inner->setHeaders($headers);

    }

    
    function addHeader($name, $value) {

        $this->inner->addHeader($name, $value);

    }

    
    function addHeaders(array $headers) {

        $this->inner->addHeaders($headers);

    }


    
    function removeHeader($name) {

        return $this->inner->removeHeader($name);

    }

    
    function setHttpVersion($version) {

        $this->inner->setHttpVersion($version);

    }

    
    function getHttpVersion() {

        return $this->inner->getHttpVersion();

    }

}
