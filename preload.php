<?php

require_once 'vendor/autoload.php';

include_once 'classes/dBug.php';
include_once 'classes/functions.php';

//database settings
define("DB_HOST"    , '127.0.0.1');
define("DB_LOGIN"   , 'root');
define("DB_PASSWORD", '');
define("DB_NAME"    , 'routes');

//connect to database
$dsn = sprintf('mysql:host=%s;dbname=%s', DB_HOST, DB_NAME);
$options = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8, sql_mode = "";',
    PDO::MYSQL_ATTR_LOCAL_INFILE => true,
); 
$db = new PDO($dsn, DB_LOGIN, DB_PASSWORD, $options);

if(empty($db))
{
  exit("Cannot connect to database");
}
