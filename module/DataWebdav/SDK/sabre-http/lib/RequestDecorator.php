<?php

namespace Sabre\HTTP;


class RequestDecorator implements RequestInterface {

    use MessageDecoratorTrait;

    
    function __construct(RequestInterface $inner) {

        $this->inner = $inner;

    }

    
    function getMethod() {

        return $this->inner->getMethod();

    }

    
    function setMethod($method) {

        $this->inner->setMethod($method);

    }

    
    function getUrl() {

        return $this->inner->getUrl();

    }

    
    function setUrl($url) {

        $this->inner->setUrl($url);

    }

    
    function getAbsoluteUrl() {

        return $this->inner->getAbsoluteUrl();

    }

    
    function setAbsoluteUrl($url) {

        $this->inner->setAbsoluteUrl($url);

    }

    
    function getBaseUrl() {

        return $this->inner->getBaseUrl();

    }

    
    function setBaseUrl($url) {

        $this->inner->setBaseUrl($url);

    }

    
    function getPath() {

        return $this->inner->getPath();

    }

    
    function getQueryParameters() {

        return $this->inner->getQueryParameters();

    }

    
    function getPostData() {

        return $this->inner->getPostData();

    }

    
    function setPostData(array $postData) {

        $this->inner->setPostData($postData);

    }


    
    function getRawServerValue($valueName) {

        return $this->inner->getRawServerValue($valueName);

    }

    
    function setRawServerData(array $data) {

        $this->inner->setRawServerData($data);

    }

    
    function __toString() {

        return $this->inner->__toString();

    }
}
