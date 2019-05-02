<?php

namespace MKCG\DBAL\Filters\Traits;

trait GetterTrait
{
    public function __get($name)
    {
        return $this->{$name} ?? null;
    }
}
