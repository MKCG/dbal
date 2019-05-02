<?php

namespace MKCG\DBAL\Filters;

abstract class MultiFilter extends CommonFilter
{
    private $filters;

    public function __construct(CommonFilter ...$filters)
    {
        $this->filters = $filters;
    }

    public function getFilters() : array
    {
        return $this->filters;
    }
}
