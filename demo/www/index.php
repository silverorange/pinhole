<?php

require_once __DIR__ . '/../vendor/autoload.php';

$config_filename = __DIR__ . '/../demo.ini';
$app = new Application('pinhole-demo', $config_filename);
$app->run();

?>
