<?php

namespace Sabre\CalDAV\Xml\Notification;

use Sabre\CalDAV;
use Sabre\CalDAV\SharingPlugin as SharingPlugin;
use Sabre\DAV;
use Sabre\Xml\Writer;


class Invite implements NotificationInterface {

    
    protected $id;

    
    protected $dtStamp;

    
    protected $href;

    
    protected $type;

    
    protected $readOnly;

    
    protected $hostUrl;

    
    protected $organizer;

    
    protected $commonName;

    
    protected $firstName;

    
    protected $lastName;

    
    protected $summary;

    
    protected $etag;

    
    protected $supportedComponents;

    
    function __construct(array $values) {

        $required = [
            'id',
            'etag',
            'href',
            'dtStamp',
            'type',
            'readOnly',
            'hostUrl',
            'organizer',
        ];
        foreach ($required as $item) {
            if (!isset($values[$item])) {
                throw new \InvalidArgumentException($item . ' is a required constructor option');
            }
        }

        foreach ($values as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new \InvalidArgumentException('Unknown option: ' . $key);
            }
            $this->$key = $value;
        }

    }

    
    function xmlSerialize(Writer $writer) {

        $writer->writeElement('{' . CalDAV\Plugin::NS_CALENDARSERVER . '}invite-notification');

    }

    
    function xmlSerializeFull(Writer $writer) {

        $cs = '{' . CalDAV\Plugin::NS_CALENDARSERVER . '}';

        $this->dtStamp->setTimezone(new \DateTimezone('GMT'));
        $writer->writeElement($cs . 'dtstamp', $this->dtStamp->format('Ymd\\THis\\Z'));

        $writer->startElement($cs . 'invite-notification');

        $writer->writeElement($cs . 'uid', $this->id);
        $writer->writeElement('{DAV:}href', $this->href);

        switch ($this->type) {

            case DAV\Sharing\Plugin::INVITE_ACCEPTED :
                $writer->writeElement($cs . 'invite-accepted');
                break;
            case DAV\Sharing\Plugin::INVITE_NORESPONSE :
                $writer->writeElement($cs . 'invite-noresponse');
                break;

        }

        $writer->writeElement($cs . 'hosturl', [
            '{DAV:}href' => $writer->contextUri . $this->hostUrl
            ]);

        if ($this->summary) {
            $writer->writeElement($cs . 'summary', $this->summary);
        }

        $writer->startElement($cs . 'access');
        if ($this->readOnly) {
            $writer->writeElement($cs . 'read');
        } else {
            $writer->writeElement($cs . 'read-write');
        }
        $writer->endElement(); 
        $writer->startElement($cs . 'organizer');
                        if (strtolower(substr($this->organizer, 0, 7)) === 'mailto:') {
            $writer->writeElement('{DAV:}href', $this->organizer);
        } else {
            $writer->writeElement('{DAV:}href', $writer->contextUri . $this->organizer);
        }
        if ($this->commonName) {
            $writer->writeElement($cs . 'common-name', $this->commonName);
        }
        if ($this->firstName) {
            $writer->writeElement($cs . 'first-name', $this->firstName);
        }
        if ($this->lastName) {
            $writer->writeElement($cs . 'last-name', $this->lastName);
        }
        $writer->endElement(); 
        if ($this->commonName) {
            $writer->writeElement($cs . 'organizer-cn', $this->commonName);
        }
        if ($this->firstName) {
            $writer->writeElement($cs . 'organizer-first', $this->firstName);
        }
        if ($this->lastName) {
            $writer->writeElement($cs . 'organizer-last', $this->lastName);
        }
        if ($this->supportedComponents) {
            $writer->writeElement('{' . CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set', $this->supportedComponents);
        }

        $writer->endElement(); 
    }

    
    function getId() {

        return $this->id;

    }

    
    function getETag() {

        return $this->etag;

    }

}
