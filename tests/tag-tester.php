<?php

require_once 'PackageConfig.php';

PackageConfig::addPackage('pinhole', 'work-gauthierm');
PackageConfig::addPackage('swat', 'work-gauthierm');
PackageConfig::addPackage('site', 'work-gauthierm');

require_once 'MDB2.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Pinhole/PinholeTagFactory.php';

$dsn = 'pgsql://php@192.168.0.26/gallery?sslmode=disable';
$connection = MDB2::connect($dsn);
PinholeTagFactory::setDefaultDatabase($connection);

function test_tag($string)
{
	$tag = PinholeTagFactory::get($string);
	if ($tag) {
		echo "=> ", $tag, ': "', $tag->getTitle(), "\"\n";
		echo "   Photos: ";
		foreach ($tag->getPhotos() as $photo) {
			echo $photo->id, ' ';
		}
		echo "\n";
	} else {
		echo "=> {$string}: *** error loading tag ***\n";
	}
}

// Tag tests

$start_time = microtime(true);

echo "Tag Tests:\n\n";

test_tag('date.week=2002-01-04'); // test date tag
test_tag('date.date=20002-01-04'); // test invalid date tag
test_tag('geo.lat=25'); // test machine tag
test_tag('christmas2001'); // test regular tag

// TagList tests

require_once 'Pinhole/PinholeTagList.php';

echo "\nTagList Tests:\n\n";

$tag_list = new PinholeTagList('christmas2001/date.year=2007/date.month=4');
$tag_list->setDatabase($connection);

echo "iterating list:\n";
foreach ($tag_list as $key => $tag) {
	echo '=> ', $key, ' => ', $tag->getTitle(), "\n";
}

echo "\n", $tag_list->getPhotoCount(), " photos in tag list:\n=> ";

foreach ($tag_list->getPhotos() as $photo) {
	echo $photo->id, ' ';
}
echo "\n";

$end_time = microtime(true);
echo "\ntotal time: ", ($end_time - $start_time) * 1000, "ms\n";

/*
require_once 'Pinhole/dataobjects/PinholeTagWrapper.php';

$sql = 'select * from PinholeTag limit 10';
$tags = SwatDB::query($connection, $sql, 'PinholeTagWrapper');
foreach ($tags as $tag) {
	echo $tag->getTitle(), "\n";
}
*/

?>
