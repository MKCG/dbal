<?php

namespace MKCG\DBAL\Driver;

use MKCG\DBAL\Filters\CommonFilter;

abstract class AbstractDriver implements DriverInterface
{
    public function supports(CommonFilter $filter) : bool
    {
        return in_array($filter->getFilterType(), $this->supportedFilters());
    }
}
