<?php

namespace MKCG\DBAL\Interpreters;

class QueryHolder
{
    public $text;

    public function __construct($text)
    {
        $this->text = $text;
    }
}
