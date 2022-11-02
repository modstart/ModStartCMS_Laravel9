<?php

namespace Sabre\HTTP;


interface ResponseInterface extends MessageInterface {

    
    function getStatus();

    
    function getStatusText();

    
    function setStatus($status);

}
