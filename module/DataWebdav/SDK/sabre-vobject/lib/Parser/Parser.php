<?php

namespace Sabre\VObject\Parser;


abstract class Parser
{
    
    const OPTION_FORGIVING = 1;

    
    const OPTION_IGNORE_INVALID_LINES = 2;

    
    protected $options;

    
    public function __construct($input = null, $options = 0)
    {
        if (!is_null($input)) {
            $this->setInput($input);
        }
        $this->options = $options;
    }

    
    abstract public function parse($input = null, $options = 0);

    
    abstract public function setInput($input);
}
