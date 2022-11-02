<?php

namespace Sabre\CalDAV\Xml\Notification;

use Sabre\CalDAV;
use Sabre\CalDAV\SharingPlugin;
use Sabre\DAV;
use Sabre\Xml\Writer;


class InviteReply implements NotificationInterface {

    
    protected $id;

    
    protected $dtStamp;

    
    protected $inReplyTo;

    
    protected $href;

    
    protected $type;

    
    protected $hostUrl;

    
    protected $summary;

    
    protected $etag;

    
    function __construct(array $values) {

        $required = [
            'id',
            'etag',
            'href',
            'dtStamp',
            'inReplyTo',
            'type',
            'hostUrl',
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

        $writer->writeElement('{' . CalDAV\Plugin::NS_CALENDARSERVER . '}invite-reply');

    }

    
    function xmlSerializeFull(Writer $writer) {

        $cs = '{' . CalDAV\Plugin::NS_CALENDARSERVER . '}';

        $this->dtStamp->setTimezone(new \DateTimezone('GMT'));
        $writer->writeElement($cs . 'dtstamp', $this->dtStamp->format('Ymd\\THis\\Z'));

        $writer->startElement($cs . 'invite-reply');

        $writer->writeElement($cs . 'uid', $this->id);
        $writer->writeElement($cs . 'in-reply-to', $this->inReplyTo);
        $writer->writeElement('{DAV:}href', $this->href);

        switch ($this->type) {

            case DAV\Sharing\Plugin::INVITE_ACCEPTED :
                $writer->writeElement($cs . 'invite-accepted');
                break;
            case DAV\Sharing\Plugin::INVITE_DECLINED :
                $writer->writeElement($cs . 'invite-declined');
                break;

        }

        $writer->writeElement($cs . 'hosturl', [
            '{DAV:}href' => $writer->contextUri . $this->hostUrl
            ]);

        if ($this->summary) {
            $writer->writeElement($cs . 'summary', $this->summary);
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
