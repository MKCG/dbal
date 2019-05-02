<?php

namespace MKCG\DBAL\Driver;

use MKCG\DBAL\Filters\{
    TermFilter,
    TermsFilter,
    LikeFilter,
    RangeFilter,
    ShouldFilter,
    AndFilter,
    OrFilter,
    NotFilter
};

class ElasticsearchDriver implements AbstractDriver
{
    public function supportedFilters() : array
    {
        return [
            TermFilter::getFilterType(),
            TermsFilter::getFilterType(),
            LikeFilter::getFilterType(),
            RangeFilter::getFilterType(),
            ShouldFilter::getFilterType(),
            AndFilter::getFilterType(),
            OrFilter::getFilterType(),
            NotFilter::getFilterType(),
        ];
    }
}
