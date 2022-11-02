<?php

namespace Sabre\HTTP;


class ClientHttpException extends \Exception implements HttpException {

    
    protected $response;

    
    function __construct(ResponseInterface $response) {

        $this->response = $response;
        parent::__construct($response->getStatusText(), $response->getStatus());

    }

    
    function getHttpStatus() {

        return $this->response->getStatus();

    }

    
    function getResponse() {

        return $this->response;

    }

}
