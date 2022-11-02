<?php

namespace Sabre\VObject\Splitter;

use Sabre\VObject;
use Sabre\VObject\Parser\MimeDir;


class VCard implements SplitterInterface
{
    
    protected $input;

    
    protected $parser;

    
    public function __construct($input, $options = 0)
    {
        $this->input = $input;
        $this->parser = new MimeDir($input, $options);
    }

    
    public function getNext()
    {
        try {
            $object = $this->parser->parse();

            if (!$object instanceof VObject\Component\VCard) {
                throw new VObject\ParseException('The supplied input contained non-VCARD data.');
            }
        } catch (VObject\EofException $e) {
            return;
        }

        return $object;
    }
}
