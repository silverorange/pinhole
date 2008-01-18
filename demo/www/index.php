<?php

echo ini_get('include_path');
echo '<br />';
@include_once 'PackageConfig.php';
if (class_exists('PackageConfig')) {
	PackageConfig::addPackage('swat');
	PackageConfig::addPackage('site');
	PackageConfig::addPackage('pinhole');
}

echo ini_get('include_path');
require_once '../include/Application.php';

$config_filename = dirname(__FILE__).'/../demo.ini';
$app = new Application('pinhole-demo', $config_filename);
$app->run();

?>
