<?php

namespace Sabre\DAV\Xml\Property;

use Sabre\Xml\Element\XmlFragment;
use Sabre\Xml\Reader;


class Complex extends XmlFragment {

    
    static function xmlDeserialize(Reader $reader) {

        $xml = $reader->readInnerXml();

        if ($reader->nodeType === Reader::ELEMENT && $reader->isEmptyElement) {
                        $reader->next();
            return null;
        }
                                $reader->read();

        $nonText = false;
        $text = '';

        while (true) {

            switch ($reader->nodeType) {
                case Reader::ELEMENT :
                    $nonText = true;
                    $reader->next();
                    continue 2;
                case Reader::TEXT :
                case Reader::CDATA :
                    $text .= $reader->value;
                    break;
                case Reader::END_ELEMENT :
                    break 2;
            }
            $reader->read();

        }

                $reader->read();

        if ($nonText) {
            $new = new self($xml);
            return $new;
        } else {
            return $text;
        }

    }


}
