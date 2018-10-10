<?php

include_once 'preload.php';

use GraphAware\Neo4j\Client\ClientBuilder;

$client = ClientBuilder::create()
    ->addConnection('bolt', 'bolt://neo4j:1@localhost:7687')
    ->build();

$sql = 'SELECT * FROM code_pairs WHERE `total_time` IS NULL ORDER BY RAND() LIMIT 1000000';
$records = query($sql)->fetchAll(PDO::FETCH_ASSOC);

foreach ($records as $row) {
    print_r(array(
            ':from' =>$row['from'],
            ':to' =>$row['to']
        ));
    $item = query('SELECT total_time FROM code_pairs WHERE `from`=:from AND `to`=:to', array(
        ':from' =>$row['from'],
        ':to' =>$row['to']
    ))->fetchColumn();
    if(!empty($item['total_time'])) {
        echo 'Already processed', chr(10);
        continue;
    }
    

    $query = 'MATCH (start:Station {code: "'. $row['from'].'"}), (end:Station {code: "'. $row['to'].'"})
    CALL algo.kShortestPaths.stream(start, end, 1, "time" ,{})

    YIELD index, nodeIds, path, costs
    RETURN [node in algo.getNodesById(nodeIds) | node.code] AS places,
           reduce(acc = 0.0, time in costs | acc + time) AS totalTime';
    $result = $client->run($query);

    $foundRecord = current($result->getRecords());
    if (empty($foundRecord)) {
        query('UPDATE code_pairs SET `total_time` = -1 WHERE `from`=:from AND `to`=:to', array(
            ':from' =>$row['from'],
            ':to' =>$row['to']
        ));
        echo 'Failed', chr(10);
        continue;
    }

    query('UPDATE code_pairs SET `path`=:path, `total_time` = :total_time WHERE `from`=:from AND `to`=:to', array(
        ':path' => json_encode($foundRecord->value('places')),
        ':total_time' => $foundRecord->value('totalTime'),
        ':from' =>$row['from'],
        ':to' =>$row['to']
    ));
    print_r(array(
        ':path' => json_encode($foundRecord->value('places')),
        ':total_time' => $foundRecord->value('totalTime')
    ));
    echo chr(10);

}
