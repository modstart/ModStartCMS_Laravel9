<?php

namespace Sabre\HTTP;


interface MessageInterface {

    
    function getBodyAsStream();

    
    function getBodyAsString();

    
    function getBody();

    
    function setBody($body);

    
    function getHeaders();

    
    function hasHeader($name);

    
    function getHeader($name);

    
    function getHeaderAsArray($name);

    
    function setHeader($name, $value);

    
    function setHeaders(array $headers);

    
    function addHeader($name, $value);

    
    function addHeaders(array $headers);

    
    function removeHeader($name);

    
    function setHttpVersion($version);

    
    function getHttpVersion();

}
