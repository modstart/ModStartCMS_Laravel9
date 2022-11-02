<?php

namespace Sabre\HTTP;

use InvalidArgumentException;
use Sabre\Uri;


class Request extends Message implements RequestInterface {

    
    protected $method;

    
    protected $url;

    
    function __construct($method = null, $url = null, array $headers = null, $body = null) {

        if (is_array($method)) {
            throw new InvalidArgumentException('The first argument for this constructor should be a string or null, not an array. Did you upgrade from sabre/http 1.0 to 2.0?');
        }
        if (!is_null($method))      $this->setMethod($method);
        if (!is_null($url))         $this->setUrl($url);
        if (!is_null($headers))     $this->setHeaders($headers);
        if (!is_null($body))        $this->setBody($body);

    }

    
    function getMethod() {

        return $this->method;

    }

    
    function setMethod($method) {

        $this->method = $method;

    }

    
    function getUrl() {

        return $this->url;

    }

    
    function setUrl($url) {

        $this->url = $url;

    }

    
    function getQueryParameters() {

        $url = $this->getUrl();
        if (($index = strpos($url, '?')) === false) {
            return [];
        } else {
            parse_str(substr($url, $index + 1), $queryParams);
            return $queryParams;
        }

    }

    
    function setAbsoluteUrl($url) {

        $this->absoluteUrl = $url;

    }

    
    function getAbsoluteUrl() {

        return $this->absoluteUrl;

    }

    
    protected $baseUrl = '/';

    
    function setBaseUrl($url) {

        $this->baseUrl = $url;

    }

    
    function getBaseUrl() {

        return $this->baseUrl;

    }

    
    function getPath() {

                $uri = str_replace('//', '/', $this->getUrl());

        $uri = Uri\normalize($uri);
        $baseUri = Uri\normalize($this->getBaseUrl());

        if (strpos($uri, $baseUri) === 0) {

                        list($uri) = explode('?', $uri);
            return trim(URLUtil::decodePath(substr($uri, strlen($baseUri))), '/');

        }
                        elseif ($uri . '/' === $baseUri) {

            return '';

        }

        throw new \LogicException('Requested uri (' . $this->getUrl() . ') is out of base uri (' . $this->getBaseUrl() . ')');
    }

    
    protected $postData = [];

    
    function setPostData(array $postData) {

        $this->postData = $postData;

    }

    
    function getPostData() {

        return $this->postData;

    }

    
    protected $rawServerData;

    
    function getRawServerValue($valueName) {

        if (isset($this->rawServerData[$valueName])) {
            return $this->rawServerData[$valueName];
        }

    }

    
    function setRawServerData(array $data) {

        $this->rawServerData = $data;

    }

    
    function __toString() {

        $out = $this->getMethod() . ' ' . $this->getUrl() . ' HTTP/' . $this->getHTTPVersion() . "\r\n";

        foreach ($this->getHeaders() as $key => $value) {
            foreach ($value as $v) {
                if ($key === 'Authorization') {
                    list($v) = explode(' ', $v, 2);
                    $v .= ' REDACTED';
                }
                $out .= $key . ": " . $v . "\r\n";
            }
        }
        $out .= "\r\n";
        $out .= $this->getBodyAsString();

        return $out;

    }

}
