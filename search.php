<?php

include_once 'preload.php';

use Doctrine\OrientDB\Graph\Graph;
use Doctrine\OrientDB\Graph\Vertex;
use Doctrine\OrientDB\Graph\Algorithm\Dijkstra;

$sql = 'SELECT DISTINCT `from` FROM `code_pairs` WHERE `train_changes`=0';
$records = query($sql)->fetchAll(PDO::FETCH_ASSOC);
$registry = array();

$graph = new Graph();

foreach ($records as $row) {
    // $rome      = new Vertex('Rome');
    $vertexFrom = $registry[$row['from']] = new Vertex($row['from']);

    $sql = 'SELECT `to`, TIME_TO_SEC(shortest_time) as "time" FROM `code_pairs` WHERE `train_changes`=0 AND `from` = :from';
    $pairs = query($sql, array(':from' => $row['from']))->fetchAll(PDO::FETCH_ASSOC);
    foreach ($pairs as $pair) {
        if (empty($registry[$pair['to']])) {
            $registry[$pair['to']] = new Vertex($pair['to']);
        } 
        $vertexTo = $registry[$pair['to']];
        $vertexFrom->connect($vertexTo, $pair['time']);
    }
    $graph->add($vertexFrom);
}

$from = 'AAP';
$to = 'FPK';

$algorithm = new Dijkstra($graph);
$algorithm->setStartingVertex($registry[$from]);
$algorithm->setEndingVertex($registry[$to]);

echo $algorithm->getLiteralShortestPath() . ": time " . $algorithm->getDistance() / 60, chr(10);
//print_r($algorithm->solve());

echo "\n";
