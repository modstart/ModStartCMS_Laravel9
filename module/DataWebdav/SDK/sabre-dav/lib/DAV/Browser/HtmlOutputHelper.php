<?php

namespace Sabre\DAV\Browser;

use Sabre\Uri;
use Sabre\Xml\Service as XmlService;


class HtmlOutputHelper {

    
    protected $baseUri;

    
    protected $namespaceMap;

    
    function __construct($baseUri, array $namespaceMap) {

        $this->baseUri = $baseUri;
        $this->namespaceMap = $namespaceMap;

    }

    
    function fullUrl($path) {

        return Uri\resolve($this->baseUri, $path);

    }

    
    function h($input) {

        return htmlspecialchars($input, ENT_COMPAT, 'UTF-8');

    }

    
    function link($url, $label = null) {

        $url = $this->h($this->fullUrl($url));
        return '<a href="' . $url . '">' . ($label ? $this->h($label) : $url) . '</a>';

    }

    
    function xmlName($element) {

        list($ns, $localName) = XmlService::parseClarkNotation($element);
        if (isset($this->namespaceMap[$ns])) {
            $propName = $this->namespaceMap[$ns] . ':' . $localName;
        } else {
            $propName = $element;
        }
        return "<span title=\"" . $this->h($element) . "\">" . $this->h($propName) . "</span>";

    }

}
