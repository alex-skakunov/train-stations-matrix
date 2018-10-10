<?php

function query($sql, $replacements=null) {
    global $db;
    $stmt = $db->prepare($sql);
    if (false === $stmt->execute($replacements)) {
      new dBug($sql);
      error_log(print_r($stmt->errorInfo(), 1));
      throw new Exception($stmt->errorInfo()[2], $stmt->errorInfo()[1]);
    }
    return $stmt;
}
