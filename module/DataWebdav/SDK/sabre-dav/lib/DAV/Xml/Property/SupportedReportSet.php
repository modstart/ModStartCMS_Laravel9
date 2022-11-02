<?php

namespace Sabre\DAV\Xml\Property;

use Sabre\DAV;
use Sabre\DAV\Browser\HtmlOutput;
use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\Xml\Writer;
use Sabre\Xml\XmlSerializable;


class SupportedReportSet implements XmlSerializable, HtmlOutput {

    
    protected $reports = [];

    
    function __construct($reports = null) {

        if (!is_null($reports))
            $this->addReport($reports);

    }

    
    function addReport($report) {

        $report = (array)$report;

        foreach ($report as $r) {

            if (!preg_match('/^{([^}]*)}(.*)$/', $r))
                throw new DAV\Exception('Reportname must be in clark-notation');

            $this->reports[] = $r;

        }

    }

    
    function getValue() {

        return $this->reports;

    }

    
    function has($reportName) {

        return in_array(
            $reportName,
            $this->reports
        );

    }

    
    function xmlSerialize(Writer $writer) {

        foreach ($this->getValue() as $val) {
            $writer->startElement('{DAV:}supported-report');
            $writer->startElement('{DAV:}report');
            $writer->writeElement($val);
            $writer->endElement();
            $writer->endElement();
        }

    }

    
    function toHtml(HtmlOutputHelper $html) {

        return implode(
            ', ',
            array_map([$html, 'xmlName'], $this->getValue())
        );

    }

}
