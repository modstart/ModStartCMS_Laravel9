<?php

namespace Sabre\CardDAV;

use Sabre\DAV;
use Sabre\DAVACL;


class AddressBook extends DAV\Collection implements IAddressBook, DAV\IProperties, DAVACL\IACL, DAV\Sync\ISyncCollection, DAV\IMultiGet {

    use DAVACL\ACLTrait;

    
    protected $addressBookInfo;

    
    protected $carddavBackend;

    
    function __construct(Backend\BackendInterface $carddavBackend, array $addressBookInfo) {

        $this->carddavBackend = $carddavBackend;
        $this->addressBookInfo = $addressBookInfo;

    }

    
    function getName() {

        return $this->addressBookInfo['uri'];

    }

    
    function getChild($name) {

        $obj = $this->carddavBackend->getCard($this->addressBookInfo['id'], $name);
        if (!$obj) throw new DAV\Exception\NotFound('Card not found');
        return new Card($this->carddavBackend, $this->addressBookInfo, $obj);

    }

    
    function getChildren() {

        $objs = $this->carddavBackend->getCards($this->addressBookInfo['id']);
        $children = [];
        foreach ($objs as $obj) {
            $obj['acl'] = $this->getChildACL();
            $children[] = new Card($this->carddavBackend, $this->addressBookInfo, $obj);
        }
        return $children;

    }

    
    function getMultipleChildren(array $paths) {

        $objs = $this->carddavBackend->getMultipleCards($this->addressBookInfo['id'], $paths);
        $children = [];
        foreach ($objs as $obj) {
            $obj['acl'] = $this->getChildACL();
            $children[] = new Card($this->carddavBackend, $this->addressBookInfo, $obj);
        }
        return $children;

    }

    
    function createDirectory($name) {

        throw new DAV\Exception\MethodNotAllowed('Creating collections in addressbooks is not allowed');

    }

    
    function createFile($name, $vcardData = null) {

        if (is_resource($vcardData)) {
            $vcardData = stream_get_contents($vcardData);
        }
                $vcardData = DAV\StringUtil::ensureUTF8($vcardData);

        return $this->carddavBackend->createCard($this->addressBookInfo['id'], $name, $vcardData);

    }

    
    function delete() {

        $this->carddavBackend->deleteAddressBook($this->addressBookInfo['id']);

    }

    
    function setName($newName) {

        throw new DAV\Exception\MethodNotAllowed('Renaming addressbooks is not yet supported');

    }

    
    function getLastModified() {

        return null;

    }

    
    function propPatch(DAV\PropPatch $propPatch) {

        return $this->carddavBackend->updateAddressBook($this->addressBookInfo['id'], $propPatch);

    }

    
    function getProperties($properties) {

        $response = [];
        foreach ($properties as $propertyName) {

            if (isset($this->addressBookInfo[$propertyName])) {

                $response[$propertyName] = $this->addressBookInfo[$propertyName];

            }

        }

        return $response;

    }

    
    function getOwner() {

        return $this->addressBookInfo['principaluri'];

    }


    
    function getChildACL() {

        return [
            [
                'privilege' => '{DAV:}all',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
        ];

    }


    
    function getSyncToken() {

        if (
            $this->carddavBackend instanceof Backend\SyncSupport &&
            isset($this->addressBookInfo['{DAV:}sync-token'])
        ) {
            return $this->addressBookInfo['{DAV:}sync-token'];
        }
        if (
            $this->carddavBackend instanceof Backend\SyncSupport &&
            isset($this->addressBookInfo['{http://sabredav.org/ns}sync-token'])
        ) {
            return $this->addressBookInfo['{http://sabredav.org/ns}sync-token'];
        }

    }

    
    function getChanges($syncToken, $syncLevel, $limit = null) {

        if (!$this->carddavBackend instanceof Backend\SyncSupport) {
            return null;
        }

        return $this->carddavBackend->getChangesForAddressBook(
            $this->addressBookInfo['id'],
            $syncToken,
            $syncLevel,
            $limit
        );

    }
}
