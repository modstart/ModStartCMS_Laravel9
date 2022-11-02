<?php

namespace Sabre\VObject\Parser;

use Sabre\VObject\Component;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Document;
use Sabre\VObject\EofException;
use Sabre\VObject\Node;
use Sabre\VObject\ParseException;


class MimeDir extends Parser
{
    
    protected $input;

    
    protected $root;

    
    protected $charset = 'UTF-8';

    
    protected static $SUPPORTED_CHARSETS = [
        'UTF-8',
        'ISO-8859-1',
        'Windows-1252',
    ];

    
    public function parse($input = null, $options = 0)
    {
        $this->root = null;

        if (!is_null($input)) {
            $this->setInput($input);
        }

        if (0 !== $options) {
            $this->options = $options;
        }

        $this->parseDocument();

        return $this->root;
    }

    
    public function setCharset($charset)
    {
        if (!in_array($charset, self::$SUPPORTED_CHARSETS)) {
            throw new \InvalidArgumentException('Unsupported encoding. (Supported encodings: '.implode(', ', self::$SUPPORTED_CHARSETS).')');
        }
        $this->charset = $charset;
    }

    
    public function setInput($input)
    {
                $this->lineIndex = 0;
        $this->startLine = 0;

        if (is_string($input)) {
                        $stream = fopen('php://temp', 'r+');
            fwrite($stream, $input);
            rewind($stream);
            $this->input = $stream;
        } elseif (is_resource($input)) {
            $this->input = $input;
        } else {
            throw new \InvalidArgumentException('This parser can only read from strings or streams.');
        }
    }

    
    protected function parseDocument()
    {
        $line = $this->readLine();

                        if (3 <= strlen($line)
            && 0xef === ord($line[0])
            && 0xbb === ord($line[1])
            && 0xbf === ord($line[2])) {
            $line = substr($line, 3);
        }

        switch (strtoupper($line)) {
            case 'BEGIN:VCALENDAR':
                $class = VCalendar::$componentMap['VCALENDAR'];
                break;
            case 'BEGIN:VCARD':
                $class = VCard::$componentMap['VCARD'];
                break;
            default:
                throw new ParseException('This parser only supports VCARD and VCALENDAR files');
        }

        $this->root = new $class([], false);

        while (true) {
                        $line = $this->readLine();
            if ('END:' === strtoupper(substr($line, 0, 4))) {
                break;
            }
            $result = $this->parseLine($line);
            if ($result) {
                $this->root->add($result);
            }
        }

        $name = strtoupper(substr($line, 4));
        if ($name !== $this->root->name) {
            throw new ParseException('Invalid MimeDir file. expected: "END:'.$this->root->name.'" got: "END:'.$name.'"');
        }
    }

    
    protected function parseLine($line)
    {
                if ('BEGIN:' === strtoupper(substr($line, 0, 6))) {
            if (substr($line, 6) === $this->root->name) {
                throw new ParseException('Invalid MimeDir file. Unexpected component: "'.$line.'" in document type '.$this->root->name);
            }
            $component = $this->root->createComponent(substr($line, 6), [], false);

            while (true) {
                                $line = $this->readLine();
                if ('END:' === strtoupper(substr($line, 0, 4))) {
                    break;
                }
                $result = $this->parseLine($line);
                if ($result) {
                    $component->add($result);
                }
            }

            $name = strtoupper(substr($line, 4));
            if ($name !== $component->name) {
                throw new ParseException('Invalid MimeDir file. expected: "END:'.$component->name.'" got: "END:'.$name.'"');
            }

            return $component;
        } else {
                        $property = $this->readProperty($line);
            if (!$property) {
                                return false;
            }

            return $property;
        }
    }

    
    protected $lineBuffer;

    
    protected $lineIndex = 0;

    
    protected $startLine = 0;

    
    protected $rawLine;

    
    protected function readLine()
    {
        if (!\is_null($this->lineBuffer)) {
            $rawLine = $this->lineBuffer;
            $this->lineBuffer = null;
        } else {
            do {
                $eof = \feof($this->input);

                $rawLine = \fgets($this->input);

                if ($eof || (\feof($this->input) && false === $rawLine)) {
                    throw new EofException('End of document reached prematurely');
                }
                if (false === $rawLine) {
                    throw new ParseException('Error reading from input stream');
                }
                $rawLine = \rtrim($rawLine, "\r\n");
            } while ('' === $rawLine);             ++$this->lineIndex;
        }
        $line = $rawLine;

        $this->startLine = $this->lineIndex;

                while (true) {
            $nextLine = \rtrim(\fgets($this->input), "\r\n");
            ++$this->lineIndex;
            if (!$nextLine) {
                break;
            }
            if ("\t" === $nextLine[0] || ' ' === $nextLine[0]) {
                $curLine = \substr($nextLine, 1);
                $line .= $curLine;
                $rawLine .= "\n ".$curLine;
            } else {
                $this->lineBuffer = $nextLine;
                break;
            }
        }
        $this->rawLine = $rawLine;

        return $line;
    }

    
    protected function readProperty($line)
    {
        if ($this->options & self::OPTION_FORGIVING) {
            $propNameToken = 'A-Z0-9\-\._\\/';
        } else {
            $propNameToken = 'A-Z0-9\-\.';
        }

        $paramNameToken = 'A-Z0-9\-';
        $safeChar = '^";:,';
        $qSafeChar = '^"';

        $regex = "/
            ^(?P<name> [$propNameToken]+ ) (?=[;:])        # property name
            |
            (?<=:)(?P<propValue> .+)$                      # property value
            |
            ;(?P<paramName> [$paramNameToken]+) (?=[=;:])  # parameter name
            |
            (=|,)(?P<paramValue>                           # parameter value
                (?: [$safeChar]*) |
                \"(?: [$qSafeChar]+)\"
            ) (?=[;:,])
            /xi";

                preg_match_all($regex, $line, $matches, PREG_SET_ORDER);

        $property = [
            'name' => null,
            'parameters' => [],
            'value' => null,
        ];

        $lastParam = null;

        
        foreach ($matches as $match) {
            if (isset($match['paramValue'])) {
                if ($match['paramValue'] && '"' === $match['paramValue'][0]) {
                    $value = substr($match['paramValue'], 1, -1);
                } else {
                    $value = $match['paramValue'];
                }

                $value = $this->unescapeParam($value);

                if (is_null($lastParam)) {
                    throw new ParseException('Invalid Mimedir file. Line starting at '.$this->startLine.' did not follow iCalendar/vCard conventions');
                }
                if (is_null($property['parameters'][$lastParam])) {
                    $property['parameters'][$lastParam] = $value;
                } elseif (is_array($property['parameters'][$lastParam])) {
                    $property['parameters'][$lastParam][] = $value;
                } else {
                    $property['parameters'][$lastParam] = [
                        $property['parameters'][$lastParam],
                        $value,
                    ];
                }
                continue;
            }
            if (isset($match['paramName'])) {
                $lastParam = strtoupper($match['paramName']);
                if (!isset($property['parameters'][$lastParam])) {
                    $property['parameters'][$lastParam] = null;
                }
                continue;
            }
            if (isset($match['propValue'])) {
                $property['value'] = $match['propValue'];
                continue;
            }
            if (isset($match['name']) && $match['name']) {
                $property['name'] = strtoupper($match['name']);
                continue;
            }

                        throw new \LogicException('This code should not be reachable');
                    }

        if (is_null($property['value'])) {
            $property['value'] = '';
        }
        if (!$property['name']) {
            if ($this->options & self::OPTION_IGNORE_INVALID_LINES) {
                return false;
            }
            throw new ParseException('Invalid Mimedir file. Line starting at '.$this->startLine.' did not follow iCalendar/vCard conventions');
        }

                                                $namedParameters = [];
        $namelessParameters = [];

        foreach ($property['parameters'] as $name => $value) {
            if (!is_null($value)) {
                $namedParameters[$name] = $value;
            } else {
                $namelessParameters[] = $name;
            }
        }

        $propObj = $this->root->createProperty($property['name'], null, $namedParameters);

        foreach ($namelessParameters as $namelessParameter) {
            $propObj->add(null, $namelessParameter);
        }

        if ('QUOTED-PRINTABLE' === strtoupper($propObj['ENCODING'])) {
            $propObj->setQuotedPrintableValue($this->extractQuotedPrintableValue());
        } else {
            $charset = $this->charset;
            if (Document::VCARD21 === $this->root->getDocumentType() && isset($propObj['CHARSET'])) {
                                $charset = (string) $propObj['CHARSET'];
            }
            switch (strtolower($charset)) {
                case 'utf-8':
                    break;
                case 'iso-8859-1':
                    $property['value'] = utf8_encode($property['value']);
                    break;
                case 'windows-1252':
                    $property['value'] = mb_convert_encoding($property['value'], 'UTF-8', $charset);
                    break;
                default:
                    throw new ParseException('Unsupported CHARSET: '.$propObj['CHARSET']);
            }
            $propObj->setRawMimeDirValue($property['value']);
        }

        return $propObj;
    }

    
    public static function unescapeValue($input, $delimiter = ';')
    {
        $regex = '#  (?: (\\\\ (?: \\\\ | N | n | ; | , ) )';
        if ($delimiter) {
            $regex .= ' | ('.$delimiter.')';
        }
        $regex .= ') #x';

        $matches = preg_split($regex, $input, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        $resultArray = [];
        $result = '';

        foreach ($matches as $match) {
            switch ($match) {
                case '\\\\':
                    $result .= '\\';
                    break;
                case '\N':
                case '\n':
                    $result .= "\n";
                    break;
                case '\;':
                    $result .= ';';
                    break;
                case '\,':
                    $result .= ',';
                    break;
                case $delimiter:
                    $resultArray[] = $result;
                    $result = '';
                    break;
                default:
                    $result .= $match;
                    break;
            }
        }

        $resultArray[] = $result;

        return $delimiter ? $resultArray : $result;
    }

    
    private function unescapeParam($input)
    {
        return
            preg_replace_callback(
                '#(\^(\^|n|\'))#',
                function ($matches) {
                    switch ($matches[2]) {
                        case 'n':
                            return "\n";
                        case '^':
                            return '^';
                        case '\'':
                            return '"';

                                        }
                                    },
                $input
            );
    }

    
    private function extractQuotedPrintableValue()
    {
                                        $regex = '/^
            (?: [^:])+ # Anything but a colon
            (?: "[^"]")* # A parameter in double quotes
            : # start of the value we really care about
            (.*)$
        /xs';

        preg_match($regex, $this->rawLine, $matches);

        $value = $matches[1];
                        $value = str_replace("\n ", "\n", $value);

                                if ($this->options & self::OPTION_FORGIVING) {
            while ('=' === substr($value, -1) && $this->lineBuffer) {
                                $this->readLine();
                                $value .= "\n".$this->rawLine;
            }
        }

        return $value;
    }
}
