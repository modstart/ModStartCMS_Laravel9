<?php

namespace Sabre\DAV;

use Sabre\HTTP;
use Sabre\Uri;


class Client extends HTTP\Client {

    
    public $xml;

    
    public $propertyMap = [];

    
    protected $baseUri;

    
    const AUTH_BASIC = 1;

    
    const AUTH_DIGEST = 2;

    
    const AUTH_NTLM = 4;

    
    const ENCODING_IDENTITY = 1;

    
    const ENCODING_DEFLATE = 2;

    
    const ENCODING_GZIP = 4;

    
    const ENCODING_ALL = 7;

    
    protected $encoding = self::ENCODING_IDENTITY;

    
    function __construct(array $settings) {

        if (!isset($settings['baseUri'])) {
            throw new \InvalidArgumentException('A baseUri must be provided');
        }

        parent::__construct();

        $this->baseUri = $settings['baseUri'];

        if (isset($settings['proxy'])) {
            $this->addCurlSetting(CURLOPT_PROXY, $settings['proxy']);
        }

        if (isset($settings['userName'])) {
            $userName = $settings['userName'];
            $password = isset($settings['password']) ? $settings['password'] : '';

            if (isset($settings['authType'])) {
                $curlType = 0;
                if ($settings['authType'] & self::AUTH_BASIC) {
                    $curlType |= CURLAUTH_BASIC;
                }
                if ($settings['authType'] & self::AUTH_DIGEST) {
                    $curlType |= CURLAUTH_DIGEST;
                }
                if ($settings['authType'] & self::AUTH_NTLM) {
                    $curlType |= CURLAUTH_NTLM;
                }
            } else {
                $curlType = CURLAUTH_BASIC | CURLAUTH_DIGEST;
            }

            $this->addCurlSetting(CURLOPT_HTTPAUTH, $curlType);
            $this->addCurlSetting(CURLOPT_USERPWD, $userName . ':' . $password);

        }

        if (isset($settings['encoding'])) {
            $encoding = $settings['encoding'];

            $encodings = [];
            if ($encoding & self::ENCODING_IDENTITY) {
                $encodings[] = 'identity';
            }
            if ($encoding & self::ENCODING_DEFLATE) {
                $encodings[] = 'deflate';
            }
            if ($encoding & self::ENCODING_GZIP) {
                $encodings[] = 'gzip';
            }
            $this->addCurlSetting(CURLOPT_ENCODING, implode(',', $encodings));
        }

        $this->addCurlSetting(CURLOPT_USERAGENT, 'sabre-dav/' . Version::VERSION . ' (http://sabre.io/)');

        $this->xml = new Xml\Service();
                $this->propertyMap = & $this->xml->elementMap;

    }

    
    function propFind($url, array $properties, $depth = 0) {

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $root = $dom->createElementNS('DAV:', 'd:propfind');
        $prop = $dom->createElement('d:prop');

        foreach ($properties as $property) {

            list(
                $namespace,
                $elementName
            ) = \Sabre\Xml\Service::parseClarkNotation($property);

            if ($namespace === 'DAV:') {
                $element = $dom->createElement('d:' . $elementName);
            } else {
                $element = $dom->createElementNS($namespace, 'x:' . $elementName);
            }

            $prop->appendChild($element);
        }

        $dom->appendChild($root)->appendChild($prop);
        $body = $dom->saveXML();

        $url = $this->getAbsoluteUrl($url);

        $request = new HTTP\Request('PROPFIND', $url, [
            'Depth'        => $depth,
            'Content-Type' => 'application/xml'
        ], $body);

        $response = $this->send($request);

        if ((int)$response->getStatus() >= 400) {
            throw new HTTP\ClientHttpException($response);
        }

        $result = $this->parseMultiStatus($response->getBodyAsString());

                if ($depth === 0) {
            reset($result);
            $result = current($result);
            return isset($result[200]) ? $result[200] : [];
        }

        $newResult = [];
        foreach ($result as $href => $statusList) {

            $newResult[$href] = isset($statusList[200]) ? $statusList[200] : [];

        }

        return $newResult;

    }

    
    function propPatch($url, array $properties) {

        $propPatch = new Xml\Request\PropPatch();
        $propPatch->properties = $properties;
        $xml = $this->xml->write(
            '{DAV:}propertyupdate',
            $propPatch
        );

        $url = $this->getAbsoluteUrl($url);
        $request = new HTTP\Request('PROPPATCH', $url, [
            'Content-Type' => 'application/xml',
        ], $xml);
        $response = $this->send($request);

        if ($response->getStatus() >= 400) {
            throw new HTTP\ClientHttpException($response);
        }

        if ($response->getStatus() === 207) {
                                    $result = $this->parseMultiStatus($response->getBodyAsString());

            $errorProperties = [];
            foreach ($result as $href => $statusList) {
                foreach ($statusList as $status => $properties) {

                    if ($status >= 400) {
                        foreach ($properties as $propName => $propValue) {
                            $errorProperties[] = $propName . ' (' . $status . ')';
                        }
                    }

                }
            }
            if ($errorProperties) {

                throw new HTTP\ClientException('PROPPATCH failed. The following properties errored: ' . implode(', ', $errorProperties));
            }
        }
        return true;

    }

    
    function options() {

        $request = new HTTP\Request('OPTIONS', $this->getAbsoluteUrl(''));
        $response = $this->send($request);

        $dav = $response->getHeader('Dav');
        if (!$dav) {
            return [];
        }

        $features = explode(',', $dav);
        foreach ($features as &$v) {
            $v = trim($v);
        }
        return $features;

    }

    
    function request($method, $url = '', $body = null, array $headers = []) {

        $url = $this->getAbsoluteUrl($url);

        $response = $this->send(new HTTP\Request($method, $url, $headers, $body));
        return [
            'body'       => $response->getBodyAsString(),
            'statusCode' => (int)$response->getStatus(),
            'headers'    => array_change_key_case($response->getHeaders()),
        ];

    }

    
    function getAbsoluteUrl($url) {

        return Uri\resolve(
            $this->baseUri,
            $url
        );

    }

    
    function parseMultiStatus($body) {

        $multistatus = $this->xml->expect('{DAV:}multistatus', $body);

        $result = [];

        foreach ($multistatus->getResponses() as $response) {

            $result[$response->getHref()] = $response->getResponseProperties();

        }

        return $result;

    }

}
