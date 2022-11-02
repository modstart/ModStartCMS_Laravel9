<?php

namespace Sabre\VObject\Splitter;


interface SplitterInterface
{
    
    public function __construct($input);

    
    public function getNext();
}
