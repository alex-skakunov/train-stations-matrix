<?php

# this file reads the "timetable", generates pairs of stations with time between them,
# and then saves this information in "code_pairs" table
# run this file from command line:
# > php direct.php

include_once 'preload.php';

query('UPDATE `code_pairs` SET `shortest_time` = NULL, `train_changes`=NULL');

define('FILENAME', 'out.sql');
file_put_contents(FILENAME, '');
file_put_contents(FILENAME . '.raw', '');

$sqlList = array();
for ($from = 1; $from < 33; $from++) { 
    $to = $from+1;
    $sql = 'SELECT CONCAT("INSERT INTO `code_pairs` VALUES (
                \'", `StopC' . $from. '`,"\',
                \'", `StopC' . $to. '`,"\',
                \'", 
                    TIMEDIFF(STR_TO_DATE(timetable.`StopN' . ($to-1). 'AT`, "%H:%i"), STR_TO_DATE(timetable.`StopN' . $from. 'DT`, "%H:%i")),
                "\',
                0,
                \'",timetable.`OriginStationCode`, " > ", timetable.`DestinationStationCode`, "\'
              )
              ON DUPLICATE KEY UPDATE 
                `shortest_time` = VALUES(`shortest_time`),
                `train_changes` = 0,
                `train` = IF(`shortest_time` = LEAST(`shortest_time`, VALUES(`shortest_time`)),
                    `train`, VALUES(`train`))
              ;
              INSERT IGNORE INTO codes (code) VALUES (\'", `StopC' . $from. '`,"\');
              INSERT IGNORE INTO codes (code) VALUES (\'", `StopC' . $to. '`,"\');
               ") as "statement"
            FROM timetable
            WHERE `StopC' . $to . '` IS NOT NULL
              AND TRIM(`StopC' . $to . '`) <> "";';
    file_put_contents(FILENAME.'.raw', $sql . chr(10), FILE_APPEND);
    $records = query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($records as $row) {
        if (empty($row['statement'])) {
            continue;
        }
        $sqlList[] = $row['statement'];
        file_put_contents(FILENAME, $row['statement'] . chr(10), FILE_APPEND);
    }
}

foreach ($sqlList as $sql) {
    query($sql);
}