<?php

namespace Sabre\HTTP\Auth;

use Sabre\HTTP\Util;


class AWS extends AbstractAuth {

    
    private $signature = null;

    
    private $accessKey = null;

    
    public $errorCode = 0;

    const ERR_NOAWSHEADER = 1;
    const ERR_MD5CHECKSUMWRONG = 2;
    const ERR_INVALIDDATEFORMAT = 3;
    const ERR_REQUESTTIMESKEWED = 4;
    const ERR_INVALIDSIGNATURE = 5;

    
    function init() {

        $authHeader = $this->request->getHeader('Authorization');
        $authHeader = explode(' ', $authHeader);

        if ($authHeader[0] != 'AWS' || !isset($authHeader[1])) {
            $this->errorCode = self::ERR_NOAWSHEADER;
             return false;
        }

        list($this->accessKey, $this->signature) = explode(':', $authHeader[1]);

        return true;

    }

    
    function getAccessKey() {

        return $this->accessKey;

    }

    
    function validate($secretKey) {

        $contentMD5 = $this->request->getHeader('Content-MD5');

        if ($contentMD5) {
                        $body = $this->request->getBody();
            $this->request->setBody($body);

            if ($contentMD5 != base64_encode(md5($body, true))) {
                                $this->errorCode = self::ERR_MD5CHECKSUMWRONG;
                return false;
            }

        }

        if (!$requestDate = $this->request->getHeader('x-amz-date'))
            $requestDate = $this->request->getHeader('Date');

        if (!$this->validateRFC2616Date($requestDate))
            return false;

        $amzHeaders = $this->getAmzHeaders();

        $signature = base64_encode(
            $this->hmacsha1($secretKey,
                $this->request->getMethod() . "\n" .
                $contentMD5 . "\n" .
                $this->request->getHeader('Content-type') . "\n" .
                $requestDate . "\n" .
                $amzHeaders .
                $this->request->getUrl()
            )
        );

        if ($this->signature != $signature) {

            $this->errorCode = self::ERR_INVALIDSIGNATURE;
            return false;

        }

        return true;

    }


    
    function requireLogin() {

        $this->response->addHeader('WWW-Authenticate', 'AWS');
        $this->response->setStatus(401);

    }

    
    protected function validateRFC2616Date($dateHeader) {

        $date = Util::parseHTTPDate($dateHeader);

                if (!$date) {
            $this->errorCode = self::ERR_INVALIDDATEFORMAT;
            return false;
        }

        $min = new \DateTime('-15 minutes');
        $max = new \DateTime('+15 minutes');

                if ($date > $max || $date < $min) {
            $this->errorCode = self::ERR_REQUESTTIMESKEWED;
            return false;
        }

        return $date;

    }

    
    protected function getAmzHeaders() {

        $amzHeaders = [];
        $headers = $this->request->getHeaders();
        foreach ($headers as $headerName => $headerValue) {
            if (strpos(strtolower($headerName), 'x-amz-') === 0) {
                $amzHeaders[strtolower($headerName)] = str_replace(["\r\n"], [' '], $headerValue[0]) . "\n";
            }
        }
        ksort($amzHeaders);

        $headerStr = '';
        foreach ($amzHeaders as $h => $v) {
            $headerStr .= $h . ':' . $v;
        }

        return $headerStr;

    }

    
    private function hmacsha1($key, $message) {

        if (function_exists('hash_hmac')) {
            return hash_hmac('sha1', $message, $key, true);
        }

        $blocksize = 64;
        if (strlen($key) > $blocksize) {
            $key = pack('H*', sha1($key));
        }
        $key = str_pad($key, $blocksize, chr(0x00));
        $ipad = str_repeat(chr(0x36), $blocksize);
        $opad = str_repeat(chr(0x5c), $blocksize);
        $hmac = pack('H*', sha1(($key ^ $opad) . pack('H*', sha1(($key ^ $ipad) . $message))));
        return $hmac;

    }

}
