<?php

namespace Sabre\HTTP;


class Sapi {

    
    static function getRequest() {

        $r = self::createFromServerArray($_SERVER);
        $r->setBody(fopen('php://input', 'r'));
        $r->setPostData($_POST);
        return $r;

    }

    
    static function sendResponse(ResponseInterface $response) {

        header('HTTP/' . $response->getHttpVersion() . ' ' . $response->getStatus() . ' ' . $response->getStatusText());
        foreach ($response->getHeaders() as $key => $value) {

            foreach ($value as $k => $v) {
                if ($k === 0) {
                    header($key . ': ' . $v);
                } else {
                    header($key . ': ' . $v, false);
                }
            }

        }

        $body = $response->getBody();
        if (is_null($body)) return;

        $contentLength = $response->getHeader('Content-Length');
        if ($contentLength !== null) {
            $output = fopen('php://output', 'wb');
            if (is_resource($body) && get_resource_type($body) == 'stream') {
                if (PHP_INT_SIZE !== 4){
                                        stream_copy_to_stream($body, $output, $contentLength);
                } else {
                                        while (!feof($body)) {
                        fwrite($output, fread($body, 8192));
                    }
                }
            } else {
                fwrite($output, $body, $contentLength);
            }
        } else {
            file_put_contents('php://output', $body);
        }

        if (is_resource($body)) {
            fclose($body);
        }

    }

    
    static function createFromServerArray(array $serverArray) {

        $headers = [];
        $method = null;
        $url = null;
        $httpVersion = '1.1';

        $protocol = 'http';
        $hostName = 'localhost';

        foreach ($serverArray as $key => $value) {

            switch ($key) {

                case 'SERVER_PROTOCOL' :
                    if ($value === 'HTTP/1.0') {
                        $httpVersion = '1.0';
                    }
                    break;
                case 'REQUEST_METHOD' :
                    $method = $value;
                    break;
                case 'REQUEST_URI' :
                    $url = $value;
                    break;

                                case 'CONTENT_TYPE' :
                    $headers['Content-Type'] = $value;
                    break;
                case 'CONTENT_LENGTH' :
                    $headers['Content-Length'] = $value;
                    break;

                                                case 'PHP_AUTH_USER' :
                    if (isset($serverArray['PHP_AUTH_PW'])) {
                        $headers['Authorization'] = 'Basic ' . base64_encode($value . ':' . $serverArray['PHP_AUTH_PW']);
                    }
                    break;

                                case 'PHP_AUTH_DIGEST' :
                    $headers['Authorization'] = 'Digest ' . $value;
                    break;

                                                case 'REDIRECT_HTTP_AUTHORIZATION' :
                    $headers['Authorization'] = $value;
                    break;

                case 'HTTP_HOST' :
                    $hostName = $value;
                    $headers['Host'] = $value;
                    break;

                case 'HTTPS' :
                    if (!empty($value) && $value !== 'off') {
                        $protocol = 'https';
                    }
                    break;

                default :
                    if (substr($key, 0, 5) === 'HTTP_') {
                        
                                                $header = strtolower(substr($key, 5));

                                                                        $header = ucwords(str_replace('_', ' ', $header));

                                                $header = str_replace(' ', '-', $header);
                        $headers[$header] = $value;

                    }
                    break;


            }

        }

        $r = new Request($method, $url, $headers);
        $r->setHttpVersion($httpVersion);
        $r->setRawServerData($serverArray);
        $r->setAbsoluteUrl($protocol . '://' . $hostName . $url);
        return $r;

    }

}
