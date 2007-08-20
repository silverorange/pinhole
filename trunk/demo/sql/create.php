#!/usr/bin/php
<?php

// set if necessary so that pear packages are in include path
//ini_set('include_path', '.:/usr/share/pear');

require('Creation/CreationProcess.php');

$process = new CreationProcess();

if ($_SERVER['argc'] < 2) {
	echo "Usage ./create.php <dsn> <sql files>...\n";
	exit();
}

$args = $_SERVER['argv'];
array_shift($args);
$process->dsn = array_shift($args);


foreach ($args as $arg) {
	if (substr($arg, -3) === 'sql')
		$process->addFile($arg);
	else
		echo "$arg: does not end with sql, ignoring\n";
}

$process->run();

?>
