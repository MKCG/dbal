<?php

namespace MKCG\DBAL\Filters;

use MKCG\DBAL\Filters\Traits\{
    FieldTrait
};

abstract class FieldFilter extends CommonFilter
{
    use FieldTrait;

    public function __construct(string $field)
    {
        $this->setField($field);
    }
}
