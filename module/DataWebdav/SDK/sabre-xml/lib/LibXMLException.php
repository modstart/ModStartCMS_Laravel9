<?php

namespace Sabre\Xml;

use
    LibXMLError;


class LibXMLException extends ParseException {

    
    protected $errors;

    
    function __construct(array $errors, $code = null, Exception $previousException = null) {

        $this->errors = $errors;
        parent::__construct($errors[0]->message . ' on line ' . $errors[0]->line . ', column ' . $errors[0]->column, $code, $previousException);

    }

    
    function getErrors() {

        return $this->errors;

    }

}
