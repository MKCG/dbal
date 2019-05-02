<?php

namespace MKCG\DBAL\Filters;

use MKCG\DBAL\Filters\Traits\ValueTrait;

class TermFilter extends FieldFilter
{
    use ValueTrait;

    public function __construct(string $field, $value)
    {
        parent::__construct($field);
        $this->setValue($value);
    }
}
