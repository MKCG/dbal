<?php

namespace MKCG\DBAL\Interpreters\SQL;

class SelectStatement
{
    public $fields;
    public $table;
    public $joins = [];
    public $conditions = [];
    public $limit;
    public $offset;
}
