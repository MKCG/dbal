<?php

namespace MKCG\DBAL\Filters\Traits;

trait FieldTrait
{
    private $field;

    public function getField() : string
    {
        return $this->field;
    }

    private function setField($field) : self
    {
        $this->field = $field;

        return $this;
    }
}
