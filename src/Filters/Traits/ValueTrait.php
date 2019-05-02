<?php

namespace MKCG\DBAL\Filters\Traits;

trait ValueTrait
{
    private $value;

    public function getValue()
    {
        return $this->value;
    }

    private function setValue($value) : self
    {
        $this->value = $value;
        return $this;
    }
}
