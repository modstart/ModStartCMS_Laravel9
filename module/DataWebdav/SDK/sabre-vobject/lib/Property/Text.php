<?php

namespace Sabre\VObject\Property;

use Sabre\VObject\Component;
use Sabre\VObject\Document;
use Sabre\VObject\Parser\MimeDir;
use Sabre\VObject\Property;
use Sabre\Xml;


class Text extends Property
{
    
    public $delimiter = ',';

    
    protected $structuredValues = [
                'N',
        'ADR',
        'ORG',
        'GENDER',
        'CLIENTPIDMAP',

                'REQUEST-STATUS',
    ];

    
    protected $minimumPropertyValues = [
        'N' => 5,
        'ADR' => 7,
    ];

    
    public function __construct(Component $root, $name, $value = null, array $parameters = [], $group = null)
    {
                                                if (in_array($name, $this->structuredValues)) {
            $this->delimiter = ';';
        }

        parent::__construct($root, $name, $value, $parameters, $group);
    }

    
    public function setRawMimeDirValue($val)
    {
        $this->setValue(MimeDir::unescapeValue($val, $this->delimiter));
    }

    
    public function setQuotedPrintableValue($val)
    {
        $val = quoted_printable_decode($val);

                                                        $regex = '# (?<!\\\\) ; #x';
        $matches = preg_split($regex, $val);
        $this->setValue($matches);
    }

    
    public function getRawMimeDirValue()
    {
        $val = $this->getParts();

        if (isset($this->minimumPropertyValues[$this->name])) {
            $val = array_pad($val, $this->minimumPropertyValues[$this->name], '');
        }

        foreach ($val as &$item) {
            if (!is_array($item)) {
                $item = [$item];
            }

            foreach ($item as &$subItem) {
                $subItem = strtr(
                    $subItem,
                    [
                        '\\' => '\\\\',
                        ';' => '\;',
                        ',' => '\,',
                        "\n" => '\n',
                        "\r" => '',
                    ]
                );
            }
            $item = implode(',', $item);
        }

        return implode($this->delimiter, $val);
    }

    
    public function getJsonValue()
    {
                                if (in_array($this->name, $this->structuredValues)) {
            return [$this->getParts()];
        }

        return $this->getParts();
    }

    
    public function getValueType()
    {
        return 'TEXT';
    }

    
    public function serialize()
    {
                if (Document::VCARD21 !== $this->root->getDocumentType()) {
            return parent::serialize();
        }

        $val = $this->getParts();

        if (isset($this->minimumPropertyValues[$this->name])) {
            $val = \array_pad($val, $this->minimumPropertyValues[$this->name], '');
        }

                        if (\count($val) > 1) {
            foreach ($val as $k => $v) {
                $val[$k] = \str_replace(';', '\;', $v);
            }
            $val = \implode(';', $val);
        } else {
            $val = $val[0];
        }

        $str = $this->name;
        if ($this->group) {
            $str = $this->group.'.'.$this->name;
        }
        foreach ($this->parameters as $param) {
            if ('QUOTED-PRINTABLE' === $param->getValue()) {
                continue;
            }
            $str .= ';'.$param->serialize();
        }

                        if (false !== \strpos($val, "\n")) {
            $str .= ';ENCODING=QUOTED-PRINTABLE:';
            $lastLine = $str;
            $out = null;

                                                            for ($ii = 0; $ii < \strlen($val); ++$ii) {
                $ord = \ord($val[$ii]);
                                if ($ord >= 32 && $ord <= 126) {
                    $lastLine .= $val[$ii];
                } else {
                    $lastLine .= '='.\strtoupper(\bin2hex($val[$ii]));
                }
                if (\strlen($lastLine) >= 75) {
                                        $out .= $lastLine."=\r\n ";
                    $lastLine = null;
                }
            }
            if (!\is_null($lastLine)) {
                $out .= $lastLine."\r\n";
            }

            return $out;
        } else {
            $str .= ':'.$val;

            $str = \preg_replace(
                '/(
                    (?:^.)?         # 1 additional byte in first line because of missing single space (see next line)
                    .{1,74}         # max 75 bytes per line (1 byte is used for a single space added after every CRLF)
                    (?![\x80-\xbf]) # prevent splitting multibyte characters
                )/x',
                "$1\r\n ",
                $str
            );

                        return \substr($str, 0, -1);
        }
    }

    
    protected function xmlSerializeValue(Xml\Writer $writer)
    {
        $values = $this->getParts();

        $map = function ($items) use ($values, $writer) {
            foreach ($items as $i => $item) {
                $writer->writeElement(
                    $item,
                    !empty($values[$i]) ? $values[$i] : null
                );
            }
        };

        switch ($this->name) {
                                                            case 'REQUEST-STATUS':
                $writer->writeElement('code', $values[0]);
                $writer->writeElement('description', $values[1]);

                if (isset($values[2])) {
                    $writer->writeElement('data', $values[2]);
                }
                break;

            case 'N':
                $map([
                    'surname',
                    'given',
                    'additional',
                    'prefix',
                    'suffix',
                ]);
                break;

            case 'GENDER':
                $map([
                    'sex',
                    'text',
                ]);
                break;

            case 'ADR':
                $map([
                    'pobox',
                    'ext',
                    'street',
                    'locality',
                    'region',
                    'code',
                    'country',
                ]);
                break;

            case 'CLIENTPIDMAP':
                $map([
                    'sourceid',
                    'uri',
                ]);
                break;

            default:
                parent::xmlSerializeValue($writer);
        }
    }

    
    public function validate($options = 0)
    {
        $warnings = parent::validate($options);

        if (isset($this->minimumPropertyValues[$this->name])) {
            $minimum = $this->minimumPropertyValues[$this->name];
            $parts = $this->getParts();
            if (count($parts) < $minimum) {
                $warnings[] = [
                    'level' => $options & self::REPAIR ? 1 : 3,
                    'message' => 'The '.$this->name.' property must have at least '.$minimum.' values. It only has '.count($parts),
                    'node' => $this,
                ];
                if ($options & self::REPAIR) {
                    $parts = array_pad($parts, $minimum, '');
                    $this->setParts($parts);
                }
            }
        }

        return $warnings;
    }
}
