<?php

namespace MKCG\DBAL\Filters;

use MKCG\DBAL\Filters\Traits\{
    LowerboundTrait,
    UpperboundTrait
};

class RangeFilter extends FieldFilter
{
    use LowerboundTrait,
        UpperboundTrait;

    public function __construct(string $field, $lowerbound, $upperbound)
    {
        parent::__construct($field);

        $this->setLowerbound($lowerbound)
            ->setUpperbound($upperbound);
    }
}
