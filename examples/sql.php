<?php

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use MKCG\DBAL\Interpreters\SQLInterpreter;
use MKCG\DBAL\Interpreters\SQL;

$queries = [
    '
        SELECT
            t1.*,
            t2.f1 as aliasF1,
            t2.f2,
            table_3.*
        FROM
           db.table_1 as t1
        JOIN table_2 as t2
        JOIN table_3
        WHERE
            t0.f0 LIKE "hello"
            AND
            t1.f0 NOT IN (7, 8, 9, 15.3, "7", true, null)
            AND
            t1.f1  NOT  IN  ("v1", "v2", "v3")
            AND t1.f2 = 4
            AND (
                db.t1.f3 = 5
                OR t1.f4 = 6
                OR t1.f5 NOT IN (7, 8, 9)
            )
        LIMIT 10, 30
        '
    ,
    'SELECT * FROM database.table JOIN database2.table2 JOIN database3.table3',
    '
        SELECT
            table.*,
            table2.id,
            table2.name,
            table3.*
        FROM database.table
        JOIN database2.table2
        JOIN database3.table3
        WHERE table.foo = "world"
        LIMIT 20
    '
];

// $selectQuery = '
// SELECT
//     t1.*,
//     t2.f1,
//     t2.f2,
//     table_3.*
// FROM
//    db.table_1 as t1
// JOIN table_2 as t2
// JOIN table_3
// WHERE
//     t0.f0 LIKE :p1
//     AND
//     t1.f0 NOT IN (:p2)
//     AND
//     t1.f1  NOT  IN  (:p3)
//     AND t1.f2 = 4
//     AND (
//         db.t1.f3 = :p4
//         OR t1.f4 = :p5
//         OR t1.f5 NOT IN (:p6)
//     )
// LIMIT 10, 30
// '
// ;


$interpreter = new SQLInterpreter();
$sql = new SQL();

$startedAt = microtime(true);

foreach ($queries as $query) {
    $statement = $sql->interpret($query);
    $statement = $interpreter->parse($query);
    var_dump($statement);
}

$took = microtime(true) - $startedAt;
echo "Took " . round($took * 1000, 2) . "ms\n";
