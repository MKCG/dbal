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

class MongoDbDriver implements AbstractDriver
{
    public function supportedFilters() : array
    {
        return [
            TermFilter::getFilterType(),
            TermsFilter::getFilterType(),
            LikeFilter::getFilterType(),
            RangeFilter::getFilterType(),
            AndFilter::getFilterType(),
            OrFilter::getFilterType(),
            NotFilter::getFilterType(),
        ];
    }
}
