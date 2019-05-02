<?php

namespace MKCG\DBAL\Driver;

use MKCG\DBAL\Filters\{
    TermFilter,
    TermsFilter,
    RangeFilter,
    AndFilter
};

class CassandraDriver implements AbstractDriver
{
    public function supportedFilters() : array
    {
        return [
            TermFilter::getFilterType(),
            TermsFilter::getFilterType(),
            RangeFilter::getFilterType(),
            AndFilter::getFilterType(),
        ];
    }
}
