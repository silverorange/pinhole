<?php

@include_once 'PackageConfig.php';
if (class_exists('PackageConfig')) {
	PackageConfig::addPackage('swat');
	PackageConfig::addPackage('site');
	PackageConfig::addPackage('pinhole');
	PackageConfig::addPackage('yui');
	PackageConfig::addpackage('recaptcha');
}

require_once 'Swat/exceptions/SwatException.php';
require_once 'Site/SiteExceptionLogger.php';
require_once 'Swat/SwatError.php';
require_once 'Site/SiteErrorLogger.php';
require_once '../include/Application.php';

$config_filename = dirname(__FILE__).'/../demo.ini';
$app = new Application('pinholedemo');
$app->config->setFilename($config_filename);
$app->config->load();

if (isset($app->config->exceptions->log_location))
	SwatException::setLogger(new SiteExceptionLogger(
		$app->config->exceptions->log_location,
		$app->config->exceptions->base_uri));

if (isset($app->config->errors->log_location))
	SwatError::setLogger(new SiteErrorLogger(
		$app->config->errors->log_location,
		$app->config->errors->base_uri));

$app->database->dsn = $app->config->database->dsn;
$app->setBaseUri($app->config->uri->base);
$app->setSecureBaseUri($app->config->uri->secure_base);
$app->default_time_zone = new Date_TimeZone($app->config->default_time_zone);
$app->run();

?>
