<?php

namespace MKCG\DBAL;

use MKCG\DBAL\Filters\{
    TermFilter,
    TermsFilter,
    RangeFilter,
    LikeFilter,
    AndFilter,
    OrFilter,
    NotFilter,
    ShouldFilter
};

class Parser
{
    public static function parse(array $query)
    {
        try {
            return (new self())->buildFilter($query);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function buildFilter(array $query)
    {
        $query = $this->normalizeQuery($query);

        if (!isset($query['_type']) || !is_string($query['_type'])) {
            throw new \Exception('Undefined query _type');
        }

        switch ($query['_type']) {
            case 'should':
                return $this->buildShouldFilter($query);

            case 'and':
                return $this->buildAndFilter($query);

            case 'or':
                return $this->buildOrFilter($query);

            case 'not':
                return $this->buildNotFilter($query);

            case 'term':
                return $this->buildTermFilter($query);

            case 'terms':
                return $this->buildTermsFilter($query);

            case 'range':
                return $this->buildRangeFilter($query);

            case 'like':
                return $this->buildLikeFilter($query);

            default:
                throw new \Exception("Unknown type " . $query['_type']);
        }
    }

    private function buildTermFilter(array $query)
    {
        $field = $this->extractField($query);
        $value = $this->extractValue($query);

        return new TermFilter($field, $value);
    }

    private function buildTermsFilter(array $query)
    {
        $field = $this->extractField($query);

        if (!isset($query['value'])) {
            throw new \Exception("Missing value");
        }

        $values = is_array($query['value'])
            ? $query['value']
            : [$query['value']];

        array_walk($values, function($value) {
            $type = strtolower(gettype($value));

            if (!in_array($type, ['string', 'null', 'boolean', 'integer', 'float'])) {
                throw new \Exception("Invalid value");
            }
        });

        return new TermsFilter($field, $values);
    }

    private function buildLikeFilter(array $query)
    {
        if (isset($query['maxtypo']) && (!is_numeric($query['maxtypo']) || $query['maxtypo'] < 0)) {
            throw new \Exception('Unexpected maxTypo value');
        }

        $field = $this->extractField($query);
        $value = $this->extractValue($query);
        $maxTypo = (int) ($query['maxtypo'] ?? 0);

        return new LikeFilter($field, $value, $maxTypo);
    }

    private function buildRangeFilter(array $query)
    {
        $field = $this->extractField($query);
        $lowerbound = $this->extractRangeValue($query, 'lowerbound');
        $upperbound = $this->extractRangeValue($query, 'upperbound');

        return new RangeFilter($field, $lowerbound, $upperbound);
    }

    private function buildShouldFilter(array $query)
    {
        if (isset($query['minshould']) && (!is_numeric($query['minshould']) || $query['minshould'] < 0)) {
            throw new \Exception('Unexpected minShould value');
        }

        $minShould = (int) ($query['minShould'] ?? 1);
        $filters = $this->makeFilterCollection($query);

        return new ShouldFilter($minShould, ...$filters);
    }

    private function buildAndFilter(array $query)
    {
        $filters = $this->makeFilterCollection($query);
        return new AndFilter(...$filters);
    }

    private function buildOrFilter(array $query)
    {
        $filters = $this->makeFilterCollection($query);
        return new OrFilter(...$filters);
    }

    private function buildNotFilter(array $query)
    {
        $filters = $this->makeFilterCollection($query);
        return new NotFilter(...$filters);
    }

    private function makeFilterCollection(array $query)
    {
        if (!isset($query['filters']) || !is_array($query['filters'])) {
            return [];
        }

        return array_map(function($filter) {
            return $this->buildFilter($filter);
        }, $query['filters']);
    }

    private function extractField(array $query)
    {
        if (!isset($query['field']) || !is_string($query['field']) || empty(trim($query['field']))) {
            throw new \Exception("Invalid field name");            
        }

        return trim(strtolower($query['field']));
    }

    private function extractValue(array $query)
    {
        if (!isset($query['value'])) {
            throw new \Exception("Missing value");
        }

        $type = strtolower(gettype($query['value']));

        if (!in_array($type, ['string', 'null', 'boolean', 'integer', 'float'])) {
            throw new \Exception('Invalid value');
        }

        return $query['value'];
    }

    private function extractRangeValue(array $query, string $field)
    {
        if (!isset($query[$field])) {
            return null;
        }

        $type = strtolower(gettype($query[$field]));

        if (!in_array($type, ['string', 'integer', 'float', 'null'])) {
            throw new \Exception('Invalid range value');
        }

        return $query[$field];
    }

    private function normalizeQuery(array $query)
    {
        return array_combine(
            array_map('trim', array_map('strtolower', array_keys($query))),
            array_values($query)
        );
    }
}
