<?php

namespace Sabre\CardDAV;

use Sabre\DAV;
use Sabre\DAVACL;


class Card extends DAV\File implements ICard, DAVACL\IACL {

    use DAVACL\ACLTrait;

    
    protected $carddavBackend;

    
    protected $cardData;

    
    protected $addressBookInfo;

    
    function __construct(Backend\BackendInterface $carddavBackend, array $addressBookInfo, array $cardData) {

        $this->carddavBackend = $carddavBackend;
        $this->addressBookInfo = $addressBookInfo;
        $this->cardData = $cardData;

    }

    
    function getName() {

        return $this->cardData['uri'];

    }

    
    function get() {

                        if (!isset($this->cardData['carddata'])) {
            $this->cardData = $this->carddavBackend->getCard($this->addressBookInfo['id'], $this->cardData['uri']);
        }
        return $this->cardData['carddata'];

    }

    
    function put($cardData) {

        if (is_resource($cardData))
            $cardData = stream_get_contents($cardData);

                $cardData = DAV\StringUtil::ensureUTF8($cardData);

        $etag = $this->carddavBackend->updateCard($this->addressBookInfo['id'], $this->cardData['uri'], $cardData);
        $this->cardData['carddata'] = $cardData;
        $this->cardData['etag'] = $etag;

        return $etag;

    }

    
    function delete() {

        $this->carddavBackend->deleteCard($this->addressBookInfo['id'], $this->cardData['uri']);

    }

    
    function getContentType() {

        return 'text/vcard; charset=utf-8';

    }

    
    function getETag() {

        if (isset($this->cardData['etag'])) {
            return $this->cardData['etag'];
        } else {
            $data = $this->get();
            if (is_string($data)) {
                return '"' . md5($data) . '"';
            } else {
                                return null;
            }
        }

    }

    
    function getLastModified() {

        return isset($this->cardData['lastmodified']) ? $this->cardData['lastmodified'] : null;

    }

    
    function getSize() {

        if (array_key_exists('size', $this->cardData)) {
            return $this->cardData['size'];
        } else {
            return strlen($this->get());
        }

    }

    
    function getOwner() {

        return $this->addressBookInfo['principaluri'];

    }


    
    function getACL() {

                if (isset($this->cardData['acl'])) {
            return $this->cardData['acl'];
        }

        return [
            [
                'privilege' => '{DAV:}all',
                'principal' => $this->addressBookInfo['principaluri'],
                'protected' => true,
            ],
        ];

    }

}
