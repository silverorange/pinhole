<?php

require_once 'PEAR/PackageFileManager2.php';

$version = '1.2.62';
$notes = <<<EOT
see ChangeLog
EOT;

$description =<<<EOT
A package for building gallery sites.
EOT;

$package = new PEAR_PackageFileManager2();
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$result = $package->setOptions(
	array(
		'filelistgenerator' => 'svn',
		'simpleoutput'      => true,
		'baseinstalldir'    => '/',
		'packagedirectory'  => './',
		'dir_roles'         => array(
			'Pinhole'      => 'php',
			'www'          => 'data',
			'demo'         => 'data',
			'sql'          => 'data',
			'dav'          => 'data',
			'dependencies' => 'data',
		),
	)
);

$package->setPackage('Pinhole');
$package->setSummary('Framework for building gallery sites');
$package->setDescription($description);
$package->setChannel('pear.silverorange.com');
$package->setPackageType('php');
$package->setLicense('LGPL', 'http://www.gnu.org/copyleft/lesser.html');

$package->setReleaseVersion($version);
$package->setReleaseStability('stable');
$package->setAPIVersion('0.0.1');
$package->setAPIStability('stable');
$package->setNotes($notes);

$package->addIgnore('package.php');

$package->addMaintainer('lead', 'nrf', 'Nathan Fredrickson', 'nathan@silverorange.com');
$package->addMaintainer('lead', 'gauthierm', 'Mike Gauthier', 'mike@silverorange.com');
$package->addMaintainer('lead', 'nick', 'Nick Burka', 'nick@silverorange.com');

$package->addReplacement(
	'Pinhole/Pinhole.php',
	'pear-config',
	'@data-dir@',
	'data_dir');

$package->addReplacement(
	'Pinhole/pages/PinholeWebDavServerPage.php',
	'pear-config',
	'@data-dir@',
	'data_dir'
);

$package->setPhpDep('5.1.5');
$package->setPearinstallerDep('1.4.0');
$package->addPackageDepWithChannel('required', 'Swat', 'pear.silverorange.com', '1.4.57');
$package->addPackageDepWithChannel('required', 'Site', 'pear.silverorange.com', '1.5.3');
$package->addPackageDepWithChannel('required', 'Admin', 'pear.silverorange.com', '1.3.42');
$package->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', '2.2.2');
$package->addPackageDepWithChannel('required', 'Yui', 'pear.silverorange.com', '1.0.6');
$package->addPackageDepWithChannel('required', 'NateGoSearch', 'pear.silverorange.com', '1.0.19');
$package->addPackageDepWithChannel('optional', 'Services_Akismet2', 'pear.php.net', '0.2.0');
$package->addExtensionDep('required', 'imagick', '2.0.0');
$package->addExtensionDep('required', 'iconv');
$package->generateContents();

if (isset($_GET['make']) || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')) {
	$package->writePackageFile();
} else {
	$package->debugPackageFile();
}

?>
