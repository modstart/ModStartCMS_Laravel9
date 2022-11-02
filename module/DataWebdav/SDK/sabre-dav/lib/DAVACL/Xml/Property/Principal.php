<?php

namespace Sabre\DAVACL\Xml\Property;

use Sabre\DAV;
use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\DAV\Exception\BadRequest;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;


class Principal extends DAV\Xml\Property\Href {

    
    const UNAUTHENTICATED = 1;

    
    const AUTHENTICATED = 2;

    
    const HREF = 3;

    
    const ALL = 4;

    
    protected $type;

    
    function __construct($type, $href = null) {

        $this->type = $type;
        if ($type === self::HREF && is_null($href)) {
            throw new DAV\Exception('The href argument must be specified for the HREF principal type.');
        }
        if ($href) {
            $href = rtrim($href, '/') . '/';
            parent::__construct($href);
        }

    }

    
    function getType() {

        return $this->type;

    }


    
    function xmlSerialize(Writer $writer) {

        switch ($this->type) {

            case self::UNAUTHENTICATED :
                $writer->writeElement('{DAV:}unauthenticated');
                break;
            case self::AUTHENTICATED :
                $writer->writeElement('{DAV:}authenticated');
                break;
            case self::HREF :
                parent::xmlSerialize($writer);
                break;
            case self::ALL :
                $writer->writeElement('{DAV:}all');
                break;
        }

    }

    
    function toHtml(HtmlOutputHelper $html) {

        switch ($this->type) {

            case self::UNAUTHENTICATED :
                return '<em>unauthenticated</em>';
            case self::AUTHENTICATED :
                return '<em>authenticated</em>';
            case self::HREF :
                return parent::toHtml($html);
            case self::ALL :
                return '<em>all</em>';
        }

    }

    
    static function xmlDeserialize(Reader $reader) {

        $tree = $reader->parseInnerTree()[0];

        switch ($tree['name']) {
            case '{DAV:}unauthenticated' :
                return new self(self::UNAUTHENTICATED);
            case '{DAV:}authenticated' :
                return new self(self::AUTHENTICATED);
            case '{DAV:}href':
                return new self(self::HREF, $tree['value']);
            case '{DAV:}all':
                return new self(self::ALL);
            default :
                throw new BadRequest('Unknown or unsupported principal type: ' . $tree['name']);
        }

    }

}
