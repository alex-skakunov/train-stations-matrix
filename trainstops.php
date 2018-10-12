<?php

include_once 'preload.php';

function form_routes() {
    $routes = array();
    $pairsRecordset = query('SELECT `from`, `to`, `train` FROM `code_pairs` WHERE `train_changes`=0')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($pairsRecordset as $row) {
        $routes[$row['from']][$row['to']] = $row['train'];
    }
    return $routes;
}

$routes = form_routes();

# get items that needs processing
$sql = 'SELECT `from`, `to`, `path` FROM code_pairs
    WHERE `train_changes` IS NULL
        AND `total_time` IS NOT NULL
        AND `path` IS NOT NULL
    ';
//$sql = 'SELECT * FROM code_pairs WHERE `from`="AAP" AND `to`="ABW"';

$records = query($sql)->fetchAll(PDO::FETCH_ASSOC);
foreach ($records as $row) {
    $pathString = $row['path'];
    $path = json_decode($pathString, 1);

    $currentTrain = false;
    $trainChanges = 0;
    for ($index=0; $index < count($path) -1; $index++) { 
        $currentStation = $path[$index];
        $nextStation = $path[$index+1];
        if (empty($routes[$currentStation][$nextStation])) {
            // echo sprintf("BROKEN PATH! From %s to %s\n", $currentStation, $nextStation);
            $trainChanges = -1;
            break;
        }
        $trainOnThisStep = $routes[$currentStation][$nextStation];
        if (false === $currentTrain) {
            $currentTrain = $trainOnThisStep;
            continue;
        }


        if ($trainOnThisStep != $currentTrain) {
            $trainChanges++;
            //printf("Changing train from %s to %s\n", $currentTrain, $trainOnThisStep);
            $currentTrain = $trainOnThisStep;
        }
    }
    query('UPDATE code_pairs SET `train_changes` = :train_changes WHERE `from`=:from AND `to`=:to', array(
        ':train_changes' => $trainChanges,
        ':from' => $row['from'],
        ':to' => $row['to']
    ));
    //printf("Train changes: %d\n", $trainChanges);
}
