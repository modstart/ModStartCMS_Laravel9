<?php

namespace Sabre\HTTP;


interface RequestInterface extends MessageInterface {

    
    function getMethod();

    
    function setMethod($method);

    
    function getUrl();

    
    function setUrl($url);

    
    function getAbsoluteUrl();

    
    function setAbsoluteUrl($url);

    
    function getBaseUrl();

    
    function setBaseUrl($url);

    
    function getPath();

    
    function getQueryParameters();

    
    function getPostData();

    
    function setPostData(array $postData);

    
    function getRawServerValue($valueName);

    
    function setRawServerData(array $data);


}
