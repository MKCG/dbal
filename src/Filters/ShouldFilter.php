<?php

namespace MKCG\DBAL\Filters;

class ShouldFilter extends MultiFilter
{
    private $minShould;

    public function __construct(int $minShould = 1, CommonFilter ...$filters)
    {
        $this->minShould = $minShould;
        parent::__construct(...$filters);
    }

    public function getMinShould() : int
    {
        return $this->minShould;
    }
}
