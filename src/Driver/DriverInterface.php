<?php

namespace MKCG\DBAL\Driver;

use MKCG\DBAL\Filters\CommonFilter;

interface DriverInterface
{
    public function supports(CommonFilter $filter) : bool;
    public function supportedFilters() : array;
}
