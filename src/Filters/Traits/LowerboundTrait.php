<?php

namespace MKCG\DBAL\Filters\Traits;

trait LowerboundTrait
{
    private $lowerbound;

    public function getLowerbound()
    {
        return $this->lowerbound;
    }

    public function setLowerbound($lowerbound) : self
    {
        $this->lowerbound = $lowerbound;
        return $this;
    }
}
