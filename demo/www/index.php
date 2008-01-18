<?php

@include_once 'PackageConfig.php';
if (class_exists('PackageConfig')) {
	PackageConfig::addPackage('swat');
	PackageConfig::addPackage('site');
	PackageConfig::addPackage('pinhole');
}

require_once '../include/Application.php';

$config_filename = dirname(__FILE__).'/../demo.ini';
$app = new Application('pinhole-demo', $config_filename);
$app->run();

?>
