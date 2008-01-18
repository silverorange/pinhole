<?php

@include_once 'PackageConfig.php';
if (class_exists('PackageConfig')) {
	PackageConfig::addPackage('swat');
	PackageConfig::addPackage('site');
	PackageConfig::addPackage('admin');
	PackageConfig::addPackage('pinhole');
}

require_once 'Swat/exceptions/SwatException.php';
require_once 'Site/SiteExceptionLogger.php';
require_once 'Swat/SwatError.php';
require_once 'Site/SiteErrorLogger.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/admin/PinholeAdminApplication.php';

$app = new PinholeAdminApplication('pinholedemoadmin');
$config_filename = dirname(__FILE__).'/../../demo.ini';
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
$app->setBaseUri($app->config->uri->base.'admin/');
$app->setSecureBaseUri($app->config->uri->secure_base.'admin/');
$app->title = 'Pinhole Demo Admin';
$app->setFrontSource('Front');
$app->addComponentIncludePath('Pinhole/admin/components', 'Pinhole');
$app->addComponentIncludePath('WebStats/admin/components', 'WebStats');
$app->default_locale = 'en_CA.UTF8';
$app->default_time_zone = new Date_TimeZone($app->config->default_time_zone);
$app->session->setSavePath($app->config->session_dir.'/'.$app->id);
$app->run();

?>
