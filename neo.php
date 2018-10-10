<?php

include_once 'preload.php';

if (empty($options['host'])) {
    $options['host'] = '127.0.0.1';
}

if (empty($options['user']) || empty($options['password'])) {
    exit("Usage: php neo.php --host localhost --user neo4j --password 1234 \n");
}

use GraphAware\Neo4j\Client\ClientBuilder;

$client = ClientBuilder::create(array('timeout' => 1))
    ->addConnection('bolt', sprintf('bolt://%s:%s@%s:7687', $options['user'], $options['password'], $options['host']))
    ->build();

$sql = 'SELECT * FROM code_pairs WHERE `total_time` IS NULL';
if (!empty($options['begins'])) {
    $sql .= ' AND `from` LIKE "' . trim($options['begins']) . '%"';
}
$sql .= ' ORDER BY RAND() LIMIT 100';

while(true) {
    $records = query($sql)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($records as $row) {

        if ($row['from'] == 'BAA' && $row['to'] == 'AC2') {
            query('UPDATE code_pairs SET `total_time` = -1 WHERE `from`=:from AND `to`=:to', array(
                ':from' =>$row['from'],
                ':to' =>$row['to']
            ));
            continue;
        }

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
        CALL algo.kShortestPaths.stream(start, end, 1, "time" ,{write: false, maxDepth: 29})

        YIELD index, nodeIds, path, costs
        RETURN [node in algo.getNodesById(nodeIds) | node.code] AS places,
               reduce(acc = 0.0, time in costs | acc + time) AS totalTime';
        try {
            $result = $client->run($query);
        }
        catch(GraphAware\Neo4j\Client\Exception\Neo4jException $e) {
            query('UPDATE code_pairs SET `total_time` = -1 WHERE `from`=:from AND `to`=:to', array(
                ':from' =>$row['from'],
                ':to' =>$row['to']
            ));
            echo 'Failed', chr(10);
            continue;
        }

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
}