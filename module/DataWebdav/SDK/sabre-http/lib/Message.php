<?php

namespace Sabre\HTTP;


abstract class Message implements MessageInterface {

    
    protected $body;

    
    protected $headers = [];

    
    protected $httpVersion = '1.1';

    
    function getBodyAsStream() {

        $body = $this->getBody();
        if (is_string($body) || is_null($body)) {
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $body);
            rewind($stream);
            return $stream;
        }
        return $body;

    }

    
    function getBodyAsString() {

        $body = $this->getBody();
        if (is_string($body)) {
            return $body;
        }
        if (is_null($body)) {
            return '';
        }
        $contentLength = $this->getHeader('Content-Length');
        if (is_int($contentLength) || ctype_digit($contentLength)) {
            return stream_get_contents($body, $contentLength);
        } else {
            return stream_get_contents($body);
        }
    }

    
    function getBody() {

        return $this->body;

    }

    
    function setBody($body) {

        $this->body = $body;

    }

    
    function getHeaders() {

        $result = [];
        foreach ($this->headers as $headerInfo) {
            $result[$headerInfo[0]] = $headerInfo[1];
        }
        return $result;

    }

    
    function hasHeader($name) {

        return isset($this->headers[strtolower($name)]);

    }

    
    function getHeader($name) {

        $name = strtolower($name);

        if (isset($this->headers[$name])) {
            return implode(',', $this->headers[$name][1]);
        }
        return null;

    }

    
    function getHeaderAsArray($name) {

        $name = strtolower($name);

        if (isset($this->headers[$name])) {
            return $this->headers[$name][1];
        }

        return [];

    }

    
    function setHeader($name, $value) {

        $this->headers[strtolower($name)] = [$name, (array)$value];

    }

    
    function setHeaders(array $headers) {

        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }

    }

    
    function addHeader($name, $value) {

        $lName = strtolower($name);
        if (isset($this->headers[$lName])) {
            $this->headers[$lName][1] = array_merge(
                $this->headers[$lName][1],
                (array)$value
            );
        } else {
            $this->headers[$lName] = [
                $name,
                (array)$value
            ];
        }

    }

    
    function addHeaders(array $headers) {

        foreach ($headers as $name => $value) {
            $this->addHeader($name, $value);
        }

    }


    
    function removeHeader($name) {

        $name = strtolower($name);
        if (!isset($this->headers[$name])) {
            return false;
        }
        unset($this->headers[$name]);
        return true;

    }

    
    function setHttpVersion($version) {

        $this->httpVersion = $version;

    }

    
    function getHttpVersion() {

        return $this->httpVersion;

    }
}
