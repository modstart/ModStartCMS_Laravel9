<?php

namespace Sabre\CalDAV\Xml\Notification;

use Sabre\CalDAV\Plugin;
use Sabre\Xml\Writer;


class SystemStatus implements NotificationInterface {

    const TYPE_LOW = 1;
    const TYPE_MEDIUM = 2;
    const TYPE_HIGH = 3;

    
    protected $id;

    
    protected $type;

    
    protected $description;

    
    protected $href;

    
    protected $etag;

    
    function __construct($id, $etag, $type = self::TYPE_HIGH, $description = null, $href = null) {

        $this->id = $id;
        $this->type = $type;
        $this->description = $description;
        $this->href = $href;
        $this->etag = $etag;

    }

    
    function xmlSerialize(Writer $writer) {

        switch ($this->type) {
            case self::TYPE_LOW :
                $type = 'low';
                break;
            case self::TYPE_MEDIUM :
                $type = 'medium';
                break;
            default :
            case self::TYPE_HIGH :
                $type = 'high';
                break;
        }

        $writer->startElement('{' . Plugin::NS_CALENDARSERVER . '}systemstatus');
        $writer->writeAttribute('type', $type);
        $writer->endElement();

    }

    
    function xmlSerializeFull(Writer $writer) {

        $cs = '{' . Plugin::NS_CALENDARSERVER . '}';
        switch ($this->type) {
            case self::TYPE_LOW :
                $type = 'low';
                break;
            case self::TYPE_MEDIUM :
                $type = 'medium';
                break;
            default :
            case self::TYPE_HIGH :
                $type = 'high';
                break;
        }

        $writer->startElement($cs . 'systemstatus');
        $writer->writeAttribute('type', $type);


        if ($this->description) {
            $writer->writeElement($cs . 'description', $this->description);
        }
        if ($this->href) {
            $writer->writeElement('{DAV:}href', $this->href);
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
