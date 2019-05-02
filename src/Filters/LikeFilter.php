<?php

namespace MKCG\DBAL\Filters;

class LikeFilter extends TermFilter
{
    private $maxTypo;

    public function __construct(string $field, string $value, int $maxTypo = 0)
    {
        parent::__construct($field, $value);
        $this->maxTypo = $maxTypo;
    }

    public function getMaxTypo()
    {
        return $this->maxTypo;
    }
}
