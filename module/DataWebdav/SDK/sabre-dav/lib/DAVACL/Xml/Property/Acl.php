<?php

namespace Sabre\DAVACL\Xml\Property;

use Sabre\DAV;
use Sabre\DAV\Browser\HtmlOutput;
use Sabre\DAV\Browser\HtmlOutputHelper;
use Sabre\Xml\Element;
use Sabre\Xml\Reader;
use Sabre\Xml\Writer;


class Acl implements Element, HtmlOutput {

    
    protected $privileges;

    
    protected $prefixBaseUrl;

    
    function __construct(array $privileges, $prefixBaseUrl = true) {

        $this->privileges = $privileges;
        $this->prefixBaseUrl = $prefixBaseUrl;

    }

    
    function getPrivileges() {

        return $this->privileges;

    }

    
    function xmlSerialize(Writer $writer) {

        foreach ($this->privileges as $ace) {

            $this->serializeAce($writer, $ace);

        }

    }

    
    function toHtml(HtmlOutputHelper $html) {

        ob_start();
        echo "<table>";
        echo "<tr><th>Principal</th><th>Privilege</th><th></th></tr>";
        foreach ($this->privileges as $privilege) {

            echo '<tr>';
                        if ($privilege['principal'][0] === '{') {
                echo '<td>', $html->xmlName($privilege['principal']), '</td>';
            } else {
                echo '<td>', $html->link($privilege['principal']), '</td>';
            }
            echo '<td>', $html->xmlName($privilege['privilege']), '</td>';
            echo '<td>';
            if (!empty($privilege['protected'])) echo '(protected)';
            echo '</td>';
            echo '</tr>';

        }
        echo "</table>";
        return ob_get_clean();

    }

    
    static function xmlDeserialize(Reader $reader) {

        $elementMap = [
            '{DAV:}ace'       => 'Sabre\Xml\Element\KeyValue',
            '{DAV:}privilege' => 'Sabre\Xml\Element\Elements',
            '{DAV:}principal' => 'Sabre\DAVACL\Xml\Property\Principal',
        ];

        $privileges = [];

        foreach ((array)$reader->parseInnerTree($elementMap) as $element) {

            if ($element['name'] !== '{DAV:}ace') {
                continue;
            }
            $ace = $element['value'];

            if (empty($ace['{DAV:}principal'])) {
                throw new DAV\Exception\BadRequest('Each {DAV:}ace element must have one {DAV:}principal element');
            }
            $principal = $ace['{DAV:}principal'];

            switch ($principal->getType()) {
                case Principal::HREF :
                    $principal = $principal->getHref();
                    break;
                case Principal::AUTHENTICATED :
                    $principal = '{DAV:}authenticated';
                    break;
                case Principal::UNAUTHENTICATED :
                    $principal = '{DAV:}unauthenticated';
                    break;
                case Principal::ALL :
                    $principal = '{DAV:}all';
                    break;

            }

            $protected = array_key_exists('{DAV:}protected', $ace);

            if (!isset($ace['{DAV:}grant'])) {
                throw new DAV\Exception\NotImplemented('Every {DAV:}ace element must have a {DAV:}grant element. {DAV:}deny is not yet supported');
            }
            foreach ($ace['{DAV:}grant'] as $elem) {
                if ($elem['name'] !== '{DAV:}privilege') {
                    continue;
                }

                foreach ($elem['value'] as $priv) {
                    $privileges[] = [
                        'principal' => $principal,
                        'protected' => $protected,
                        'privilege' => $priv,
                    ];
                }

            }

        }

        return new self($privileges);

    }

    
    private function serializeAce(Writer $writer, array $ace) {

        $writer->startElement('{DAV:}ace');

        switch ($ace['principal']) {
            case '{DAV:}authenticated' :
                $principal = new Principal(Principal::AUTHENTICATED);
                break;
            case '{DAV:}unauthenticated' :
                $principal = new Principal(Principal::UNAUTHENTICATED);
                break;
            case '{DAV:}all' :
                $principal = new Principal(Principal::ALL);
                break;
            default:
                $principal = new Principal(Principal::HREF, $ace['principal']);
                break;
        }

        $writer->writeElement('{DAV:}principal', $principal);
        $writer->startElement('{DAV:}grant');
        $writer->startElement('{DAV:}privilege');

        $writer->writeElement($ace['privilege']);

        $writer->endElement();         $writer->endElement(); 
        if (!empty($ace['protected'])) {
            $writer->writeElement('{DAV:}protected');
        }

        $writer->endElement(); 
    }

}
