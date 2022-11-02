<?php

namespace Sabre\HTTP;

use Sabre\Event\EventEmitter;
use Sabre\Uri;


class Client extends EventEmitter {

    
    protected $curlSettings = [];

    
    protected $throwExceptions = false;

    
    protected $maxRedirects = 5;

    
    function __construct() {

        $this->curlSettings = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => false,
            CURLOPT_USERAGENT      => 'sabre-http/' . Version::VERSION . ' (http://sabre.io/)',
        ];

    }

    
    function send(RequestInterface $request) {

        $this->emit('beforeRequest', [$request]);

        $retryCount = 0;
        $redirects = 0;

        do {

            $doRedirect = false;
            $retry = false;

            try {

                $response = $this->doRequest($request);

                $code = (int)$response->getStatus();

                                                                                                if (in_array($code, [301, 302, 307, 308]) && $redirects < $this->maxRedirects) {

                    $oldLocation = $request->getUrl();

                                        $request = clone $request;

                                        $request->setUrl(Uri\resolve(
                        $oldLocation,
                        $response->getHeader('Location')
                    ));

                    $doRedirect = true;
                    $redirects++;

                }

                                if ($code >= 400) {

                    $this->emit('error', [$request, $response, &$retry, $retryCount]);
                    $this->emit('error:' . $code, [$request, $response, &$retry, $retryCount]);

                }

            } catch (ClientException $e) {

                $this->emit('exception', [$request, $e, &$retry, $retryCount]);

                                                                if (!$retry) {
                    throw $e;
                }

            }

            if ($retry) {
                $retryCount++;
            }

        } while ($retry || $doRedirect);

        $this->emit('afterRequest', [$request, $response]);

        if ($this->throwExceptions && $code >= 400) {
            throw new ClientHttpException($response);
        }

        return $response;

    }

    
    function sendAsync(RequestInterface $request, callable $success = null, callable $error = null) {

        $this->emit('beforeRequest', [$request]);
        $this->sendAsyncInternal($request, $success, $error);
        $this->poll();

    }


    
    function poll() {

                if (!$this->curlMultiMap) {
            return false;
        }

        do {
            $r = curl_multi_exec(
                $this->curlMultiHandle,
                $stillRunning
            );
        } while ($r === CURLM_CALL_MULTI_PERFORM);

        do {

            messageQueue:

            $status = curl_multi_info_read(
                $this->curlMultiHandle,
                $messagesInQueue
            );

            if ($status && $status['msg'] === CURLMSG_DONE) {

                $resourceId = intval($status['handle']);
                list(
                    $request,
                    $successCallback,
                    $errorCallback,
                    $retryCount,
                ) = $this->curlMultiMap[$resourceId];
                unset($this->curlMultiMap[$resourceId]);
                $curlResult = $this->parseCurlResult(curl_multi_getcontent($status['handle']), $status['handle']);
                $retry = false;

                if ($curlResult['status'] === self::STATUS_CURLERROR) {

                    $e = new ClientException($curlResult['curl_errmsg'], $curlResult['curl_errno']);
                    $this->emit('exception', [$request, $e, &$retry, $retryCount]);

                    if ($retry) {
                        $retryCount++;
                        $this->sendAsyncInternal($request, $successCallback, $errorCallback, $retryCount);
                        goto messageQueue;
                    }

                    $curlResult['request'] = $request;

                    if ($errorCallback) {
                        $errorCallback($curlResult);
                    }

                } elseif ($curlResult['status'] === self::STATUS_HTTPERROR) {

                    $this->emit('error', [$request, $curlResult['response'], &$retry, $retryCount]);
                    $this->emit('error:' . $curlResult['http_code'], [$request, $curlResult['response'], &$retry, $retryCount]);

                    if ($retry) {

                        $retryCount++;
                        $this->sendAsyncInternal($request, $successCallback, $errorCallback, $retryCount);
                        goto messageQueue;

                    }

                    $curlResult['request'] = $request;

                    if ($errorCallback) {
                        $errorCallback($curlResult);
                    }

                } else {

                    $this->emit('afterRequest', [$request, $curlResult['response']]);

                    if ($successCallback) {
                        $successCallback($curlResult['response']);
                    }

                }
            }

        } while ($messagesInQueue > 0);

        return count($this->curlMultiMap) > 0;

    }

    
    function wait() {

        do {
            curl_multi_select($this->curlMultiHandle);
            $stillRunning = $this->poll();
        } while ($stillRunning);

    }

    
    function setThrowExceptions($throwExceptions) {

        $this->throwExceptions = $throwExceptions;

    }

    
    function addCurlSetting($name, $value) {

        $this->curlSettings[$name] = $value;

    }

    
    protected function doRequest(RequestInterface $request) {

        $settings = $this->createCurlSettingsArray($request);

        if (!$this->curlHandle) {
            $this->curlHandle = curl_init();
        }

        curl_setopt_array($this->curlHandle, $settings);
        $response = $this->curlExec($this->curlHandle);
        $response = $this->parseCurlResult($response, $this->curlHandle);

        if ($response['status'] === self::STATUS_CURLERROR) {
            throw new ClientException($response['curl_errmsg'], $response['curl_errno']);
        }

        return $response['response'];

    }

    
    private $curlHandle;

    
    private $curlMultiHandle;

    
    private $curlMultiMap = [];

    
    protected function createCurlSettingsArray(RequestInterface $request) {

        $settings = $this->curlSettings;

        switch ($request->getMethod()) {
            case 'HEAD' :
                $settings[CURLOPT_NOBODY] = true;
                $settings[CURLOPT_CUSTOMREQUEST] = 'HEAD';
                $settings[CURLOPT_POSTFIELDS] = '';
                $settings[CURLOPT_PUT] = false;
                break;
            case 'GET' :
                $settings[CURLOPT_CUSTOMREQUEST] = 'GET';
                $settings[CURLOPT_POSTFIELDS] = '';
                $settings[CURLOPT_PUT] = false;
                break;
            default :
                $body = $request->getBody();
                if (is_resource($body)) {
                                                                                $settings[CURLOPT_PUT] = true;
                    $settings[CURLOPT_INFILE] = $request->getBody();
                } else {
                                                                                $settings[CURLOPT_POSTFIELDS] = (string)$body;
                }
                $settings[CURLOPT_CUSTOMREQUEST] = $request->getMethod();
                break;

        }

        $nHeaders = [];
        foreach ($request->getHeaders() as $key => $values) {

            foreach ($values as $value) {
                $nHeaders[] = $key . ': ' . $value;
            }

        }
        $settings[CURLOPT_HTTPHEADER] = $nHeaders;
        $settings[CURLOPT_URL] = $request->getUrl();
                if (defined('CURLOPT_PROTOCOLS')) {
            $settings[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }
                if (defined('CURLOPT_REDIR_PROTOCOLS')) {
            $settings[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        return $settings;

    }

    const STATUS_SUCCESS = 0;
    const STATUS_CURLERROR = 1;
    const STATUS_HTTPERROR = 2;

    
    protected function parseCurlResult($response, $curlHandle) {

        list(
            $curlInfo,
            $curlErrNo,
            $curlErrMsg
        ) = $this->curlStuff($curlHandle);

        if ($curlErrNo) {
            return [
                'status'      => self::STATUS_CURLERROR,
                'curl_errno'  => $curlErrNo,
                'curl_errmsg' => $curlErrMsg,
            ];
        }

        $headerBlob = substr($response, 0, $curlInfo['header_size']);
                                $responseBody = substr($response, $curlInfo['header_size']) ?: null;

        unset($response);

                                $headerBlob = explode("\r\n\r\n", trim($headerBlob, "\r\n"));

                $headerBlob = $headerBlob[count($headerBlob) - 1];

                $headerBlob = explode("\r\n", $headerBlob);

        $response = new Response();
        $response->setStatus($curlInfo['http_code']);

        foreach ($headerBlob as $header) {
            $parts = explode(':', $header, 2);
            if (count($parts) == 2) {
                $response->addHeader(trim($parts[0]), trim($parts[1]));
            }
        }

        $response->setBody($responseBody);

        $httpCode = intval($response->getStatus());

        return [
            'status'    => $httpCode >= 400 ? self::STATUS_HTTPERROR : self::STATUS_SUCCESS,
            'response'  => $response,
            'http_code' => $httpCode,
        ];

    }

    
    protected function sendAsyncInternal(RequestInterface $request, callable $success, callable $error, $retryCount = 0) {

        if (!$this->curlMultiHandle) {
            $this->curlMultiHandle = curl_multi_init();
        }
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            $this->createCurlSettingsArray($request)
        );
        curl_multi_add_handle($this->curlMultiHandle, $curl);
        $this->curlMultiMap[intval($curl)] = [
            $request,
            $success,
            $error,
            $retryCount
        ];

    }

    
    
    protected function curlExec($curlHandle) {

        return curl_exec($curlHandle);

    }

    
    protected function curlStuff($curlHandle) {

        return [
            curl_getinfo($curlHandle),
            curl_errno($curlHandle),
            curl_error($curlHandle),
        ];

    }
    
}
