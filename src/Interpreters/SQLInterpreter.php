<?php

namespace MKCG\DBAL\Interpreters;

use MKCG\DBAL\Interpreters\SQL\SelectStatement;
use MKCG\DBAL\Filters\{
    TermFilter,
    TermsFilter,
    RangeFilter,
    LikeFilter,
    AndFilter,
    OrFilter,
    NotFilter,
    ShouldFilter,
    CommonFilter
};

class SQLInterpreter
{
    private $conditionOperators;
    private $conditionOperatorRegex;

    public function __construct()
    {
        $this->conditionOperators = [
            '=',
            'like',
            '<>', '!=',
            'not in',
            'is null',
            'in'
        ];

        $regex = array_map(function ($operator) {
            $operator = str_replace(' ', '\s+', $operator);
            return '(?:' . $operator . ')';
        }, $this->conditionOperators);

        $this->conditionOperatorRegex = '/\s*(' . implode('|', $regex) . ')/i';
    }

    public function parse(string $text) : ?object
    {
        $query = new QueryHolder($text);
        $type = $this->getStatementType($text);

        $type !== null and $query->text = $this->removePrefix($query->text, $type);

        switch ($type) {
            case 'select':
                return $this->parseSelectStatement($query);

            case null:
            default:
                return null;
        }
    }

    private function parseSelectStatement(QueryHolder $query)
    {
        $statement = new SelectStatement();

        $this->definedSelectedFields($statement, $query)
            ->defineTableName($statement, $query);

        while (true) {
            $statementType = $this->getStatementType($query->text);

            if ($statementType !== 'join') {
                break;
            }

            $this->addJoinStatement($statement, $query);
        }

        if ($statementType === 'where') {
            preg_match('/\s*where\s/i', $query->text, $matches);
            $query->text = trim(mb_substr($query->text, mb_strlen($matches[0])));

            $statement->conditions = $this->extractConditions($statement, $query);
            $statementType = $this->getStatementType($query->text);
        }

        if ($statementType === 'limit') {
            $this->defineLimit($statement, $query);
        }

        return $statement;
    }

    private function definedSelectedFields(SelectStatement $statement, QueryHolder $query) : self
    {
        $text = $query->text;
        $regexToLocateFrom = '/\sfrom\s+\w/i';
        preg_match($regexToLocateFrom, $text, $matches);

        if (!isset($matches[0])) {
            throw new \Exception('Missing "FROM" statement');
        }

        $fromStatement = mb_substr($matches[0], 0, -1);
        $fromLength = mb_strlen($fromStatement);
        $fromPos = mb_strpos($text, $fromStatement);

        $fields = mb_substr($text, 0, $fromPos);
        $fields = mbsplit(',', $fields);
        $fields = array_map('trim', $fields);

        $statement->fields = $fields;

        $query->text = mb_substr($text, $fromLength + $fromPos);

        return $this;
    }

    private function defineTableName(SelectStatement $statement, QueryHolder $query) : self
    {
        $tableName = $this->extractTableName($query->text);

        if ($tableName === null) {
            throw new \Exception('Table name undefined');
        }

        $query->text = mb_substr($query->text, mb_strlen($tableName));
        $statement->table = trim($tableName);

        return $this;
    }

    private function addJoinStatement(SelectStatement $statement, QueryHolder $query) : self
    {
        preg_match('/\s*join\s/i', $query->text, $matches);
        $query->text = mb_substr($query->text, mb_strlen($matches[0]));

        $tableName = $this->extractTableName($query->text);

        if ($tableName === null) {
            throw new \Exception('Table name undefined in join statement');
        }

        $query->text = mb_substr($query->text, mb_strlen($tableName));
        $statement->joins[] = trim($tableName);

        return $this;
    }

    private function extractConditions(SelectStatement $statement, QueryHolder $query, int $level = 0)
    {
        $conditions = [];
        $group = null;

        while (true) {
            $type = $this->getStatementType($query->text);

            if ($type === null) {
                break;
            }

            switch ($type) {
                case ')':
                    $query->text = mb_substr($query->text, mb_strpos($query->text, '(') + 1);

                    if ($level > 0) {
                        return $conditions;
                    }

                    break;

                case '(':
                    $query->text = mb_substr($query->text, mb_strpos($query->text, '(') + 1);
                    $subConditions = $this->extractConditions($statement, $query, $level + 1);
                    $conditions[] = $subConditions;
                    break;

                case 'and':
                    $group = 'and';
                    $pos = mb_strpos(mb_strtolower($query->text), 'and');
                    $query->text = trim(mb_substr($query->text, $pos + 3));
                    break;

                case 'or':
                    $group = 'or';
                    $pos = mb_strpos(mb_strtolower($query->text), 'or');
                    $query->text = trim(mb_substr($query->text, $pos + 3));
                    break;

                default:
                    $fieldName = $this->extractFieldName($query->text);

                    if (in_array(mb_strtolower($fieldName), ['limit']) || $fieldName === null) {
                        break 2;
                    }

                    $condition = $this->extractCondition($query);
                    $conditions[] = $condition;

                    if ($group === 'and') {
                        $conditions = [new AndFilter(...$conditions)];
                        $group = null;
                    } else if ($group === 'or') {
                        $conditions = [new OrFilter(...$conditions)];
                        $group = null;
                    }

                    break;
            }
        }

        return $conditions;
    }

    private function extractCondition(QueryHolder $query) : ?CommonFilter
    {
        $fieldName = $this->extractFieldName($query->text);
        $fieldNamePos = mb_strpos($query->text, $fieldName);

        $query->text = mb_substr($query->text, $fieldNamePos + mb_strlen($fieldName));
        $operator = $this->extractConditionOperator($query);
        $values = $this->extractValues($query);

        if (!isset($values[0])) {
            throw new \Exception("Too few values defined");
        }

        if (!in_array($operator, ['in', 'not in'])) {
            if (isset($values[1])) {
                throw new \Exception("Too many values defined for the operator " . $operator);
            }

            $values = $values[0];
        }

        $condition = null;

        switch ($operator) {
            case '=':
                $condition = new TermFilter($fieldName, $values);
                break;

            case '!=':
            case '<>':
                $condition = new NotFilter(new TermFilter($fieldName, $values));
                break;

            case 'like':
                $condition = new LikeFilter($fieldName, $values);
                break;

            case 'in':
                $condition = new TermsFilter($fieldName, $values);
                break;

            case 'not in':
                $condition = new NotFilter(new TermsFilter($fieldName, $values));
                break;
        }

        return $condition;
    }

    private function defineLimit(SelectStatement $statement, QueryHolder $query) : self
    {
        preg_match('/\d+(\s*,\s*\d+)?/', $query->text, $matches);

        if (!isset($matches[0])) {
            return $this;
        }

        $limit = explode(',', $matches[0]);

        if (isset($limit[1])) {
            $statement->offset = (int) $limit[0];
            $statement->limit = (int) $limit[1];
        } else {
            $statement->offset = 0;
            $statement->limit = (int) $limit[0];
        }

        return $this;
    }

    private function extractTableName(string $text) : ?string
    {
        $text .= ' ';
        $regexForTableName = '/\s*\w+(\.\w+)?\s+(as\s+\w+\s+)?/i';
        preg_match($regexForTableName, $text, $matches);

        return isset($matches[0]) ? trim($matches[0]) : null;
    }

    private function extractFieldName(string $text) : ?string
    {
        $regexForTableName = '/\s*\w+(\.\w+)*\s+(as\s+\w+\s+)?/i';
        preg_match($regexForTableName, $text, $matches);

        return isset($matches[0]) ? trim($matches[0]) : null;
    }

    private function extractConditionOperator(QueryHolder $query) : string
    {
        preg_match($this->conditionOperatorRegex, $query->text, $matches);

        if (!isset($matches[0])) {
            throw new \Exception("Undefined operator");
        }

        $query->text = mb_substr($query->text, mb_strlen($matches[0]));
        $query->text = trim($query->text);

        $operator = trim($matches[0]);
        $operator = preg_replace('/\s+/', ' ', $operator);
        $operator = mb_strtolower($operator);

        if (!in_array($operator, $this->conditionOperators)) {
            throw new \Exception("Unknown operator : " . $operator);
        }

        return $operator;
    }

    private function extractValues(QueryHolder $query)
    {
        $values = [];

        $currentValue = null;
        $quoteType = null;
        $isEscaped = false;
        $level = 0;


        $splits = preg_split('//u', $query->text);

        foreach ($splits as $i => $token) {
            if ($token === '(') {
                $level++;
                continue;
            }

            if ($quoteType === null && in_array($token, [',', ')'])) {
                if ($currentValue !== null) {
                    $values[] = $this->castValue($currentValue);
                    $currentValue = null;
                }

                if ($token === ')') {
                    $level--;

                    if ($level === 0) {
                        break;
                    }
                }

                continue;
            }

            if ($quoteType === null && in_array($token, ['"', "'"])) {
                $quoteType = $token;
                continue;
            }

            if ($quoteType === $token && !$isEscaped) {
                $values[] = $currentValue;
                $quoteType = null;
                $currentValue = null;
                continue;
            }

            if ($quoteType !== null) {
                if ($token === '\\') {
                    if (!$isEscaped) {
                        $isEscaped = true;
                    } else {
                        $currentValue = $currentValue !== null
                            ? $currentValue . $token
                            : $token;
                        $isEscaped = false;
                    }
                } else {
                    $currentValue = $currentValue !== null
                        ? $currentValue . $token
                        : $token;
                }
            } else if (trim($token) === '') {
                if ($currentValue !== null) {
                    $values[] = $this->castValue($currentValue);

                    if ($level === 0) {
                        break;
                    }

                    $currentValue = null;
                }
            } else if (is_numeric($token) || in_array(mb_strtolower($token), ['.', 't', 'r', 'u', 'e', 'f', 'a', 'l', 's', 'n', 'l'])) {
                if (mb_strtolower($token) === 'l' && mb_strtolower($splits[$i + 1]) === 'i') {
                    $i--;
                    break;
                }

                $currentValue = $currentValue !== null
                    ? $currentValue . $token
                    : $token;
            } else {
                if ($level === 0) {
                    break;
                }
            }
        }

        $query->text = trim(mb_substr(implode('', $splits), $i));
        $values = array_unique($values);

        return $values;
    }

    private function removePrefix(string $text, string $prefix) : string
    {
        $normalized = mb_strtolower($text);
        $pos = mb_strpos($normalized, $prefix);

        return $pos !== false
            ? mb_substr($text, $pos + mb_strlen($prefix))
            : $text;
    }

    private function getStatementType(string $text) : ?string
    {
        $text = $this->normalize($text);
        $pos = mb_strpos($text, ' ');

        return $pos !== false
            ? mb_substr($text, 0, $pos)
            : null;
    }

    private function normalize(string $text) : string
    {
        $text = str_replace("\n", " ", $text);
        $text = str_replace("\t", " ", $text);
        $text = mb_strtolower($text);
        $text = trim($text);

        return $text;
    }

    private function castValue($value)
    {
        if (is_numeric($value)) {
            return strpos($value, '.') !== false
                ? (float) $value
                : (int) $value;
        }

        if (in_array(strtolower($value), ['true', 'false'])) {
            return (bool) $value;
        }

        if (strtolower($value) === 'null') {
            return null;
        }

        throw new \Exception("Unexpected value : " . $value);
    }
}
