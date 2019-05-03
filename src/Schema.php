<?php

namespace MKCG\DBAL;

class Schema
{
    private $database;
    private $collection;
    private $fields;

    public function __construct(string $database, string $collection, array $fields)
    {
        $this->fields = $fields;
        $this->database = $database;
        $this->collection = $collection;
    }

    public function getType(string $field) : string
    {
        return 'string';
    }

    public function isRequired(string $field) : bool
    {
        return false;
    }

    public function hasDefaultValue(string $field) : bool
    {
        return false;
    }

    public function getDefaultValue(string $field)
    {
        return null;
    }

    public function isFilterable(string $field) : bool
    {
        return false;
    }

    public function isAggregatable(string $field) : bool
    {
        return false;
    }

    public function aggregationIsSupported(string $field, string $aggregationType) : bool
    {
        return false;
    }
}
