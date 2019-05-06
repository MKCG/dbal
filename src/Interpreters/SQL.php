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

class SQL
{
    public function interpret(string $query)
    {
        $tokens = $this->tokenize($query);
        $statement = $this->makeStatement($tokens);
        return $statement;
    }

    private function makeStatement(array $tokens)
    {
        if (!isset($tokens[0])) {
            throw new \Exception("Undefined statement");
        }

        $type = array_shift($tokens);

        switch (strtolower($type)) {
            case 'select':
                return $this->makeSelectStatement($tokens);

            default:
                throw new \Exception("Unsupported statement type : " . $type);
                
        }
    }

    private function makeSelectStatement(array $tokens)
    {
        $statement = new SelectStatement();
        $this->defineSelectedFields($statement, $tokens);

        while (isset($tokens[0])) {
            $op = strtolower(array_shift($tokens));

            switch ($op) {
                case 'from':
                    $table = $this->extractTable($tokens);
                    $statement->table = $table;
                    break;

                case 'join':
                    $table = $this->extractTable($tokens);
                    $statement->joins[] = $table;
                    break;

                case 'where':
                    $conditionTokens = $this->extractConditionsTokens($tokens);
                    $conditions = $this->makeConditions($conditionTokens);
                    $statement->conditions = $conditions;
                    break;

                case 'limit':
                    $this->makeLimit($statement, $tokens);
                    break;
            }
        }

        return $statement;
    }

    private function defineSelectedFields(SelectStatement $statement, array &$tokens)
    {
        $fields = [];

        $currentField = (object) [
            'name' => null,
            'alias' => null
        ];

        while (isset($tokens[0])) {
            $token = array_shift($tokens);

            if (strtolower($token) === 'from') {
                array_unshift($tokens, 'from');
                break;
            }

            if ($currentField->name === null) {
                $currentField->name = $token;
            } else if ($token === ',') {
                $fields[] = $currentField;
                $currentField = (object) [
                    'name' => null,
                    'alias' => null
                ];
            } else if (strtolower($token) === 'as') {
                continue;
            } elseif ($currentField->alias === null) {
                $currentField->alias = $token;
            }
        }

        $statement->fields = $fields;
        return $this;
    }

    private function extractTable(array &$tokens)
    {
        $table = (object) [
            'name' => null,
            'alias' => null,
        ];

        $useAlias = null;

        while (isset($tokens[0])) {
            $token = array_shift($tokens);

            if ($table->name === null) {
                $table->name = $token;
            } else if ($useAlias === null && strtolower($token) === 'as') {
                $useAlias = true;
            } elseif ($useAlias === true && $table->alias === null) {
                $table->alias = $token;
            } else {
                array_unshift($tokens, $token);
                break;
            }
        }

        return $table;
    }

    private function extractConditionsTokens(array &$tokens)
    {
        $conditionTokens = [];

        while (isset($tokens[0])) {
            $token = array_shift($tokens);

            if (in_array(strtolower($token), ['limit', ';'])) {
                array_unshift($tokens, $token);
                break;
            } else {
                $conditionTokens[] = $token;
            }
        }

        return $conditionTokens;
    }

    private function makeConditions($tokens)
    {
        $conditions = [];
        $currentGroup = [];

        $isAnd = false;
        $isOr = false;
        $level = 0;

        while (isset($tokens[0])) {
            $token = array_shift($tokens);

            if (!in_array(strtolower($token), ['and', 'or', '(', ')'])) {
                $currentGroup[] = $token;
            } else if (strtolower($token) === 'and') {
                if ($currentGroup !== []) {
                    $condition = $this->makeCondition($currentGroup);
                    $conditions[] = $condition;

                    if ($isAnd) {
                        $conditions = [new AndFilter(...$conditions)];
                        $isAnd = false;
                    } else if ($isOr) {
                        $conditions = [new OrFilter(...$conditions)];
                        $isOr = false;
                    }
                }

                $isAnd = true;
                $currentGroup = [];
            } else if (strtolower($token) === 'or') {
                if ($currentGroup !== []) {
                    $condition = $this->makeCondition($currentGroup);
                    $conditions[] = $condition;

                    if ($isAnd) {
                        $conditions = [new AndFilter(...$conditions)];
                        $isAnd = false;
                    } else if ($isOr) {
                        $conditions = [new OrFilter(...$conditions)];
                        $isOr = false;
                    }
                }

                $isOr = true;
                $currentGroup = [];
            } else if ($token === '(' && isset($currentGroup[1])) {
                $level++;
                $currentGroup[] = $token;
            } else if ($token === ')' && $currentGroup !== []) {
                $level--;

                if ($level === 0 && $currentGroup !== []) {
                    $currentGroup[] = $token;
                    $condition = $this->makeCondition($currentGroup);
                    $conditions[] = $condition;
                    $currentGroup = [];
                }
            } elseif ($token === '(' && $currentGroup === []) {
                $innerTokens = [];
                $innerLevel = 0;

                while (isset($tokens[0])) {
                    $innerToken = array_shift($tokens);

                    if ($innerToken === '(') {
                        $innerLevel++;
                        $innerTokens[] = $innerToken;
                    } elseif ($innerToken === ')') {
                        $innerLevel--;

                        if ($innerLevel === 0) {
                            $innerConditions = $this->makeConditions($innerTokens);

                            if (count($innerConditions) === 1) {
                                $conditions[] = $innerConditions[0];
                            } else {
                                throw new \Exception();
                            }

                            continue 2;
                        } else {
                            $innerTokens[] = $innerToken;
                        }
                    } else {
                        $innerTokens[] = $innerToken;
                    }
                }

                throw new \Exception();
            }
        }

        if ($currentGroup !== []) {
            $condition = $this->makeCondition($currentGroup);
            $conditions[] = $condition;

            if ($isAnd) {
                $conditions = [new AndFilter(...$conditions)];
                $isAnd = false;
            } else if ($isOr) {
                $conditions = [new OrFilter(...$conditions)];
                $isOr = false;
            }
        }

        return $conditions;
    }

    private function makeCondition(array $tokens)
    {
        if (!isset($tokens[2])) {
            throw new \Exception("Invalid condition");    
        }

        $field = array_shift($tokens);
        $operator = strtolower(array_shift($tokens));

        if (isset($tokens[1]) && in_array($operator . ' '. strtolower($tokens[0]), ['not in', 'is null'])) {
            $operator .= ' ' . strtolower(array_shift($tokens));
        }

        switch ($operator) {
            case 'in':
            case 'not in':
                $values = [];

                while (isset($tokens[0])) {
                    $token = array_shift($tokens);
                    if (!in_array($token, ['(', ')', ','])) {
                        $values[] = $token;
                    }
                }

                $condition = new TermsFilter($field, $values);
                return $operator === 'not in'
                    ? new NotFilter($condition)
                    : $condition;

            case '=':
                return new TermFilter($field, $tokens[0]);

            case '!=':
            case '<>':
                return new Not(new TermsFilter($field, $tokens[0]));

            case 'like':
                return new LikeFilter($field, $tokens[0]);

            case 'is null':
                return new TermFilter($field, null);
        }
    }

    private function makeLimit(SelectStatement $statement, array &$tokens)
    {
        if (isset($tokens[2]) && is_numeric($tokens[0]) && $tokens[1] === ',' && is_numeric($tokens[2])) {
            $statement->offset = (int) $tokens[0];
            $statement->limit = (int) $tokens[2];
            array_shift($tokens);
            array_shift($tokens);
            array_shift($tokens);
        } else if (is_numeric($tokens[0])) {
            $statement->offset = 0;
            $statement->limit = $tokens[0];
            array_shift($tokens);
        } else {
            throw new \Exception("Invalid limit");
            
        }

        return $this;
    }

    private function tokenize($query)
    {
        $isInGroup = false;
        $currentGroup = null;
        $group = ['"', "'", '`'];

        $isEscaped = false;
        $escapeChar = '\\';

        $tokens = [];
        $current = '';

        foreach (preg_split('//u', $query) as $token) {
            if ($isEscaped) {
                $current .= $token;
                $isEscaped = false;
            } else if (!$isEscaped && $token === $escapeChar) {
                $isEscaped = true;
            } else if (!$isEscaped && $isInGroup && $token === $currentGroup) {
                $tokens[] = $current . $token;
                $current = '';
                $currentGroup = null;
                $isInGroup = false;
            } else if (!$isEscaped && !$isInGroup && in_array($token, $group)) {
                $isInGroup = true;

                if ($current !== '') {
                    $tokens[] = $current;
                }

                $current = $token;
                $currentGroup = $token;
            } else if (!$isEscaped && !$isInGroup && trim($token) === '') {
                if ($current !== '') {
                    $tokens[] = $current;
                    $current = '';
                }
            } else if (in_array($token, [')', '(', ',', ';'])) {
                $current !== '' and $tokens[] = $current;
                $tokens[] = $token;
                $current = '';
            } else {
                $current .= $token;
            }
        }

        if ($current !== '') {
            $tokens[] = $current;
        }

        return $tokens;
    }
}
