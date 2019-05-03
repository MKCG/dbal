<?php

namespace MKCG\DBAL\Driver;

use MKCG\DBAL\Filters\{
    TermFilter
};

class MemcachedDriver implements AbstractDriver
{
    public function supportedFilters() : array
    {
        return [
            TermFilter::getFilterType()
        ];
    }
}
