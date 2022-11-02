<?php

namespace Sabre\HTTP;


class ResponseDecorator implements ResponseInterface {

    use MessageDecoratorTrait;

    
    function __construct(ResponseInterface $inner) {

        $this->inner = $inner;

    }

    
    function getStatus() {

        return $this->inner->getStatus();

    }


    
    function getStatusText() {

        return $this->inner->getStatusText();

    }
    
    function setStatus($status) {

        $this->inner->setStatus($status);

    }

    
    function __toString() {

        return $this->inner->__toString();

    }
}
