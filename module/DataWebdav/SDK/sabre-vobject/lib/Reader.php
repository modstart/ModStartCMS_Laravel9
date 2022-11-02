<?php

namespace Sabre\VObject;


class Reader
{
    
    const OPTION_FORGIVING = 1;

    
    const OPTION_IGNORE_INVALID_LINES = 2;

    
    public static function read($data, $options = 0, $charset = 'UTF-8')
    {
        $parser = new Parser\MimeDir();
        $parser->setCharset($charset);
        $result = $parser->parse($data, $options);

        return $result;
    }

    
    public static function readJson($data, $options = 0)
    {
        $parser = new Parser\Json();
        $result = $parser->parse($data, $options);

        return $result;
    }

    
    public static function readXML($data, $options = 0)
    {
        $parser = new Parser\XML();
        $result = $parser->parse($data, $options);

        return $result;
    }
}
