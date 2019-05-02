<?php

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use MKCG\DBAL\{Query, Filter, Sorter, Parser};
use MKCG\DBAL\Filters\{TermFilter, TermsFilter, RangeFilter, LikeFilter, AndFilter, OrFilter, NotFilter, ShouldFilter};

$term = new TermFilter('firstname', 'Kévin');
$terms = new TermsFilter('skills', ['architecture', 'ddd', 'event-sourcing', 'cqrs']);
$range = new RangeFilter('updated_at', '2018-01-01', '2019-12-31');
$like = new LikeFilter('city', 'Paris', 1);

$multi = new ShouldFilter(
    2,
    new AndFilter($term, $terms, $range, $like),
    new OrFilter($term, $terms, $range, $like),
    new NotFilter($term, $terms, $range, $like)
);

$content = json_encode($multi->toArray());
$filters = json_decode($content, JSON_OBJECT_AS_ARRAY);

$query = Parser::parse($filters);

var_dump($query);
