<?php

namespace Sabre\HTTP;


class Response extends Message implements ResponseInterface {

    
    static $statusCodes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authorative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',         208 => 'Already Reported',         226 => 'IM Used',         300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',         421 => 'Misdirected Request',         422 => 'Unprocessable Entity',         423 => 'Locked',         424 => 'Failed Dependency',         426 => 'Upgrade Required',
        428 => 'Precondition Required',         429 => 'Too Many Requests',         431 => 'Request Header Fields Too Large',         451 => 'Unavailable For Legal Reasons',         500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',         508 => 'Loop Detected',         509 => 'Bandwidth Limit Exceeded',         510 => 'Not extended',
        511 => 'Network Authentication Required',     ];

    
    protected $status;

    
    protected $statusText;

    
    function __construct($status = null, array $headers = null, $body = null) {

        if (!is_null($status)) $this->setStatus($status);
        if (!is_null($headers)) $this->setHeaders($headers);
        if (!is_null($body)) $this->setBody($body);

    }


    
    function getStatus() {

        return $this->status;

    }

    
    function getStatusText() {

        return $this->statusText;

    }

    
    function setStatus($status) {

        if (ctype_digit($status) || is_int($status)) {

            $statusCode = $status;
            $statusText = isset(self::$statusCodes[$status]) ? self::$statusCodes[$status] : 'Unknown';

        } else {
            list(
                $statusCode,
                $statusText
            ) = explode(' ', $status, 2);
        }
        if ($statusCode < 100 || $statusCode > 999) {
            throw new \InvalidArgumentException('The HTTP status code must be exactly 3 digits');
        }

        $this->status = $statusCode;
        $this->statusText = $statusText;

    }

    
    function __toString() {

        $str = 'HTTP/' . $this->httpVersion . ' ' . $this->getStatus() . ' ' . $this->getStatusText() . "\r\n";
        foreach ($this->getHeaders() as $key => $value) {
            foreach ($value as $v) {
                $str .= $key . ": " . $v . "\r\n";
            }
        }
        $str .= "\r\n";
        $str .= $this->getBodyAsString();
        return $str;

    }

}
