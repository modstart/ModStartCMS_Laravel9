<?php

namespace Sabre\VObject;

use ArrayIterator;
use Sabre\Xml;


class Parameter extends Node
{
    
    public $name;

    
    public $noName = false;

    
    protected $value;

    
    public function __construct(Document $root, $name, $value = null)
    {
        $this->name = strtoupper($name);
        $this->root = $root;
        if (is_null($name)) {
            $this->noName = true;
            $this->name = static::guessParameterNameByValue($value);
        }

                                if ('' === $this->name) {
            $this->noName = false;
            $this->name = strtoupper($value);
        } else {
            $this->setValue($value);
        }
    }

    
    public static function guessParameterNameByValue($value)
    {
        switch (strtoupper($value)) {
                        case '7-BIT':
            case 'QUOTED-PRINTABLE':
            case 'BASE64':
                $name = 'ENCODING';
                break;

                        case 'WORK':
            case 'HOME':
            case 'PREF':

                        case 'DOM':
            case 'INTL':
            case 'POSTAL':
            case 'PARCEL':

                        case 'VOICE':
            case 'FAX':
            case 'MSG':
            case 'CELL':
            case 'PAGER':
            case 'BBS':
            case 'MODEM':
            case 'CAR':
            case 'ISDN':
            case 'VIDEO':

                        case 'AOL':
            case 'APPLELINK':
            case 'ATTMAIL':
            case 'CIS':
            case 'EWORLD':
            case 'INTERNET':
            case 'IBMMAIL':
            case 'MCIMAIL':
            case 'POWERSHARE':
            case 'PRODIGY':
            case 'TLX':
            case 'X400':

                        case 'GIF':
            case 'CGM':
            case 'WMF':
            case 'BMP':
            case 'DIB':
            case 'PICT':
            case 'TIFF':
            case 'PDF':
            case 'PS':
            case 'JPEG':
            case 'MPEG':
            case 'MPEG2':
            case 'AVI':
            case 'QTIME':

                        case 'WAVE':
            case 'PCM':
            case 'AIFF':

                        case 'X509':
            case 'PGP':
                $name = 'TYPE';
                break;

                        case 'INLINE':
            case 'URL':
            case 'CONTENT-ID':
            case 'CID':
                $name = 'VALUE';
                break;

            default:
                $name = '';
        }

        return $name;
    }

    
    public function setValue($value)
    {
        $this->value = $value;
    }

    
    public function getValue()
    {
        if (is_array($this->value)) {
            return implode(',', $this->value);
        } else {
            return $this->value;
        }
    }

    
    public function setParts(array $value)
    {
        $this->value = $value;
    }

    
    public function getParts()
    {
        if (is_array($this->value)) {
            return $this->value;
        } elseif (is_null($this->value)) {
            return [];
        } else {
            return [$this->value];
        }
    }

    
    public function addValue($part)
    {
        if (is_null($this->value)) {
            $this->value = $part;
        } else {
            $this->value = array_merge((array) $this->value, (array) $part);
        }
    }

    
    public function has($value)
    {
        return in_array(
            strtolower($value),
            array_map('strtolower', (array) $this->value)
        );
    }

    
    public function serialize()
    {
        $value = $this->getParts();

        if (0 === count($value)) {
            return $this->name.'=';
        }

        if (Document::VCARD21 === $this->root->getDocumentType() && $this->noName) {
            return implode(';', $value);
        }

        return $this->name.'='.array_reduce(
            $value,
            function ($out, $item) {
                if (!is_null($out)) {
                    $out .= ',';
                }

                                                                                                                                                                                                                                                                                if (!preg_match('#(?: [\n":;\^,\+] )#x', $item)) {
                    return $out.$item;
                } else {
                                                            $out .= '"'.strtr(
                        $item,
                        [
                            '^' => '^^',
                            "\n" => '^n',
                            '"' => '^\'',
                        ]
                    ).'"';

                    return $out;
                }
            }
        );
    }

    
    public function jsonSerialize()
    {
        return $this->value;
    }

    
    public function xmlSerialize(Xml\Writer $writer)
    {
        foreach (explode(',', $this->value) as $value) {
            $writer->writeElement('text', $value);
        }
    }

    
    public function __toString()
    {
        return (string) $this->getValue();
    }

    
    public function getIterator()
    {
        if (!is_null($this->iterator)) {
            return $this->iterator;
        }

        return $this->iterator = new ArrayIterator((array) $this->value);
    }
}
