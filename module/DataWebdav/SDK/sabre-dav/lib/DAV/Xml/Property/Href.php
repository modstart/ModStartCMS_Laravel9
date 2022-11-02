<?php

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV\Browser\HtmlOutput;
use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\Uri;
use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;


class Href implements Element, HtmlOutput {

    
    protected $hrefs;

    
    function __construct($hrefs) {

        if (is_string($hrefs)) {
            $hrefs = [$hrefs];
        }
        $this->hrefs = $hrefs;

    }

    
    function getHref() {

        return $this->hrefs[0];

    }

    
    function getHrefs() {

        return $this->hrefs;

    }

    
    function xmlSerialize(Writer $writer) {

        foreach ($this->getHrefs() as $href) {
            $href = Uri\resolve($writer->contextUri, $href);
            $writer->writeElement('{DAV:}href', $href);
        }

    }

    
    function toHtml(HtmlOutputHelper $html) {

        $links = [];
        foreach ($this->getHrefs() as $href) {
            $links[] = $html->link($href);
        }
        return implode('<br />', $links);

    }

    
    static function xmlDeserialize(Reader $reader) {

        $hrefs = [];
        foreach ((array)$reader->parseInnerTree() as $elem) {
            if ($elem['name'] !== '{DAV:}href')
                continue;

            $hrefs[] = $elem['value'];

        }
        if ($hrefs) {
            return new self($hrefs, false);
        }

    }

}
