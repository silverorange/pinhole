<?php

require_once '../include/Application.php';

$config_filename = dirname(__FILE__).'/../demo.ini';
$app = new Application('pinhole-demo', $config_filename);
$app->run();

?>
