<?php

@include_once 'PackageConfig.php';
if (class_exists('PackageConfig')) {
	PackageConfig::addPackage('swat');
	PackageConfig::addPackage('site', 'work-nrf');
	PackageConfig::addPackage('pinhole', 'work-nrf');
	PackageConfig::addPackage('yui');
	PackageConfig::addPackage('recaptcha');
	PackageConfig::addPath('/so/sites/veseys2/pear/lib');
}

require_once '../include/Application.php';

$config_filename = dirname(__FILE__).'/../demo.ini';
$app = new Application('pinhole-demo', $config_filename);
$app->run();

?>
