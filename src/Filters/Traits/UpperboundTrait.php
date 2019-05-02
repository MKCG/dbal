<?php

namespace MKCG\DBAL\Filters\Traits;

trait UpperboundTrait
{
    private $upperbound;

    public function geUpperbound()
    {
        return $this->upperbound;
    }

    public function setUpperbound($upperbound) : self
    {
        $this->upperbound = $upperbound;
        return $this;
    }
}
