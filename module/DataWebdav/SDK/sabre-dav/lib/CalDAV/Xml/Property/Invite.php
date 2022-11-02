<?php

namespace Sabre\CalDAV\Xml\Property;

use Sabre\CalDAV\Plugin;
use Sabre\DAV;
use Sabre\DAV\Xml\Element\Sharee;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;


class Invite implements XmlSerializable {

    
    protected $sharees;

    
    function __construct(array $sharees) {

        $this->sharees = $sharees;

    }

    
    function getValue() {

        return $this->sharees;

    }

    
    function xmlSerialize(Writer $writer) {

        $cs = '{' . Plugin::NS_CALENDARSERVER . '}';

        foreach ($this->sharees as $sharee) {

            if ($sharee->access === DAV\Sharing\Plugin::ACCESS_SHAREDOWNER) {
                $writer->startElement($cs . 'organizer');
            } else {
                $writer->startElement($cs . 'user');

                switch ($sharee->inviteStatus) {
                    case DAV\Sharing\Plugin::INVITE_ACCEPTED :
                        $writer->writeElement($cs . 'invite-accepted');
                        break;
                    case DAV\Sharing\Plugin::INVITE_DECLINED :
                        $writer->writeElement($cs . 'invite-declined');
                        break;
                    case DAV\Sharing\Plugin::INVITE_NORESPONSE :
                        $writer->writeElement($cs . 'invite-noresponse');
                        break;
                    case DAV\Sharing\Plugin::INVITE_INVALID :
                        $writer->writeElement($cs . 'invite-invalid');
                        break;
                }

                $writer->startElement($cs . 'access');
                switch ($sharee->access) {
                    case DAV\Sharing\Plugin::ACCESS_READWRITE :
                        $writer->writeElement($cs . 'read-write');
                        break;
                    case DAV\Sharing\Plugin::ACCESS_READ :
                        $writer->writeElement($cs . 'read');
                        break;

                }
                $writer->endElement(); 
            }

            $href = new DAV\Xml\Property\Href($sharee->href);
            $href->xmlSerialize($writer);

            if (isset($sharee->properties['{DAV:}displayname'])) {
                $writer->writeElement($cs . 'common-name', $sharee->properties['{DAV:}displayname']);
            }
            if ($sharee->comment) {
                $writer->writeElement($cs . 'summary', $sharee->comment);
            }
            $writer->endElement(); 
        }

    }

}
