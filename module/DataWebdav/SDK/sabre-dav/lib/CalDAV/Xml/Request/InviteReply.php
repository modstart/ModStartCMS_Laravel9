<?php

namespace Sabre\CalDAV\Xml\Request;

use Sabre\CalDAV\Plugin;
use Sabre\CalDAV\SharingPlugin;
use Sabre\DAV;
use Sabre\DAV\Exception\BadRequest;
use Sabre\Xml\Element\KeyValue;
use Sabre\Xml\Reader;
use Sabre\Xml\XmlDeserializable;


class InviteReply implements XmlDeserializable {

    
    public $href;

    
    public $calendarUri;

    
    public $inReplyTo;

    
    public $summary;

    
    public $status;

    
    function __construct($href, $calendarUri, $inReplyTo, $summary, $status) {

        $this->href = $href;
        $this->calendarUri = $calendarUri;
        $this->inReplyTo = $inReplyTo;
        $this->summary = $summary;
        $this->status = $status;

    }

    
    static function xmlDeserialize(Reader $reader) {

        $elems = KeyValue::xmlDeserialize($reader);

        $href = null;
        $calendarUri = null;
        $inReplyTo = null;
        $summary = null;
        $status = null;

        foreach ($elems as $name => $value) {

            switch ($name) {

                case '{' . Plugin::NS_CALENDARSERVER . '}hosturl' :
                    foreach ($value as $bla) {
                        if ($bla['name'] === '{DAV:}href') {
                            $calendarUri = $bla['value'];
                        }
                    }
                    break;
                case '{' . Plugin::NS_CALENDARSERVER . '}invite-accepted' :
                    $status = DAV\Sharing\Plugin::INVITE_ACCEPTED;
                    break;
                case '{' . Plugin::NS_CALENDARSERVER . '}invite-declined' :
                    $status = DAV\Sharing\Plugin::INVITE_DECLINED;
                    break;
                case '{' . Plugin::NS_CALENDARSERVER . '}in-reply-to' :
                    $inReplyTo = $value;
                    break;
                case '{' . Plugin::NS_CALENDARSERVER . '}summary' :
                    $summary = $value;
                    break;
                case '{DAV:}href' :
                    $href = $value;
                    break;
            }

        }
        if (is_null($calendarUri)) {
            throw new BadRequest('The {http://calendarserver.org/ns/}hosturl/{DAV:}href element must exist');
        }

        return new self($href, $calendarUri, $inReplyTo, $summary, $status);

    }

}
