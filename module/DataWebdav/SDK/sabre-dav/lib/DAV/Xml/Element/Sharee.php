<?php

namespace Sabre\DAV\Xml\Element;

use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Sharing\Plugin;
use Sabre\DAV\Xml\Property\Href;
use Sabre\DAV\Xml\Property\ShareAccess;
use Sabre\Xml\Deserializer;
use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;


class Sharee implements Element {

    
    public $href;

    
    public $principal;

    
    public $properties = [];

    
    public $access;

    
    public $comment;

    
    public $inviteStatus;

    
    function __construct(array $properties = []) {

        foreach ($properties as $k => $v) {

            if (property_exists($this, $k)) {
                $this->$k = $v;
            } else {
                throw new \InvalidArgumentException('Unknown property: ' . $k);
            }

        }

    }

    
    function xmlSerialize(Writer $writer) {


        $writer->write([
            new Href($this->href),
            '{DAV:}prop'         => $this->properties,
            '{DAV:}share-access' => new ShareAccess($this->access),
        ]);
        switch ($this->inviteStatus) {
            case Plugin::INVITE_NORESPONSE :
                $writer->writeElement('{DAV:}invite-noresponse');
                break;
            case Plugin::INVITE_ACCEPTED :
                $writer->writeElement('{DAV:}invite-accepted');
                break;
            case Plugin::INVITE_DECLINED :
                $writer->writeElement('{DAV:}invite-declined');
                break;
            case Plugin::INVITE_INVALID :
                $writer->writeElement('{DAV:}invite-invalid');
                break;
        }

    }

    
    static function xmlDeserialize(Reader $reader) {

                $reader->pushContext();
        $reader->elementMap['{DAV:}share-access'] = 'Sabre\DAV\Xml\Property\ShareAccess';
        $reader->elementMap['{DAV:}prop'] = 'Sabre\Xml\Deserializer\keyValue';

        $elems = Deserializer\keyValue($reader, 'DAV:');

                $reader->popContext();

        $sharee = new self();
        if (!isset($elems['href'])) {
            throw new BadRequest('Every {DAV:}sharee must have a {DAV:}href child-element');
        }
        $sharee->href = $elems['href'];

        if (isset($elems['prop'])) {
            $sharee->properties = $elems['prop'];
        }
        if (isset($elems['comment'])) {
            $sharee->comment = $elems['comment'];
        }
        if (!isset($elems['share-access'])) {
            throw new BadRequest('Every {DAV:}sharee must have a {DAV:}share-access child element');
        }
        $sharee->access = $elems['share-access']->getValue();
        return $sharee;

    }

}
