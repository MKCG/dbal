<?php

namespace MKCG\DBAL\Driver;

use MKCG\DBAL\Filters\{
    TermFilter,
    TermsFilter,
    LikeFilter,
    RangeFilter,
    AndFilter,
    OrFilter,
    NotFilter
};

class SqlDriver implements AbstractDriver
{
    public function supportedFilters() : array
    {
        return [
            TermFilter::getFilterType(),
            TermFilters::getFilterType(),
            LikeFilter::getFilterType(),
            RangeFilter::getFilterType(),
            AndFilter::getFilterType(),
            OrFilter::getFilterType(),
            NotFilter::getFilterType(),
        ];
    }
}
