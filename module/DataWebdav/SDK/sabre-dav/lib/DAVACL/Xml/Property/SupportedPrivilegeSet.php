<?php

namespace Sabre\DAVACL\Xml\Property;

use Sabre\DAV\Browser\HtmlOutput;
use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;


class SupportedPrivilegeSet implements XmlSerializable, HtmlOutput {

    
    protected $privileges;

    
    function __construct(array $privileges) {

        $this->privileges = $privileges;

    }

    
    function getValue() {

        return $this->privileges;

    }

    
    function xmlSerialize(Writer $writer) {

        $this->serializePriv($writer, '{DAV:}all', ['aggregates' => $this->privileges]);

    }

    
    function toHtml(HtmlOutputHelper $html) {

        $traverse = function($privName, $priv) use (&$traverse, $html) {
            echo "<li>";
            echo $html->xmlName($privName);
            if (isset($priv['abstract']) && $priv['abstract']) {
                echo " <i>(abstract)</i>";
            }
            if (isset($priv['description'])) {
                echo " " . $html->h($priv['description']);
            }
            if (isset($priv['aggregates'])) {
                echo "\n<ul>\n";
                foreach ($priv['aggregates'] as $subPrivName => $subPriv) {
                    $traverse($subPrivName, $subPriv);
                }
                echo "</ul>";
            }
            echo "</li>\n";
        };

        ob_start();
        echo "<ul class=\"tree\">";
        $traverse('{DAV:}all', ['aggregates' => $this->getValue()]);
        echo "</ul>\n";

        return ob_get_clean();

    }



    
    private function serializePriv(Writer $writer, $privName, $privilege) {

        $writer->startElement('{DAV:}supported-privilege');

        $writer->startElement('{DAV:}privilege');
        $writer->writeElement($privName);
        $writer->endElement(); 
        if (!empty($privilege['abstract'])) {
            $writer->writeElement('{DAV:}abstract');
        }
        if (!empty($privilege['description'])) {
            $writer->writeElement('{DAV:}description', $privilege['description']);
        }
        if (isset($privilege['aggregates'])) {
            foreach ($privilege['aggregates'] as $subPrivName => $subPrivilege) {
                $this->serializePriv($writer, $subPrivName, $subPrivilege);
            }
        }

        $writer->endElement(); 
    }

}
