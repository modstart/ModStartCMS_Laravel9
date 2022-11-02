<?php

namespace Sabre\CalDAV\Xml\Notification;

use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;


interface NotificationInterface extends XmlSerializable {

    
    function xmlSerializeFull(Writer $writer);

    
    function getId();

    
    function getETag();

}
