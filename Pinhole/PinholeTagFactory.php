<?php

require_once 'Pinhole/tags/PinholeAbstractTag.php';
require_once 'Pinhole/dataobjects/PinholeTagDataObject.php';
require_once 'MDB2.php';

/**
 * Tag factory
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeTagFactory
{
	/**
	 * @var array
	 */
	private static $tag_classes = array();

	/**
	 * @var MDB2_Driver_Common
	 */
	private static $default_database;

	/**
	 * @return PinholeAbstractTag
	 */
	public static function get($string, MDB2_Driver_Common $db = null)
	{
		if ($db === null &&
			self::$default_database instanceof MDB2_Driver_Common) {
			$db = self::$default_database;
		}

		$machine_tag_pattern = '/^([a-z]+)\.[a-z]+=[a-zA-Z0-9-\+]*$/';
		$matches = array();
		if (preg_match($machine_tag_pattern, $string, $matches) == 1) {
			$namespace = $matches[1];
		} else {
			$namespace = null;
		}

		$tag_class = self::getTagClass($namespace);

		$tag = new $tag_class();
		$valid = $tag->parse($string, $db);

		return ($valid) ? $tag : false;
	}

	public static function setDefaultDatabase(MDB2_Driver_Common $db)
	{
		self::$default_database = $db;
	}

	/**
	 * @return string
	 */
	private static function getTagClass($namespace)
	{
		$return =
			(array_key_exists($namespace, self::$tag_classes)) ?
			self::$tag_classes[$namespace] : null;

		// check if it is a user tag
		if ($return === null && $namespace === null) {
			if (!class_exists('PinholeTag'))
				require_once 'Pinhole/tags/PinholeTag.php';

			$return = 'PinholeTag';
		}

		// see if required machine tag class definition exists already
		if ($return === null) {
			$class_names = array(
				sprintf('Pinhole%sTag', ucfirst($namespace)),
				sprintf('%sTag', ucfirst($namespace)),
			);

			foreach ($class_names as $class_name) {
				if (class_exists($class_name)) {
					self::$tag_classes[$namespace] = $class_name;
					$return = $class_name;
					break;
				}
			}
		}

		// try to load machine tag class definition
		if ($return === null) {
			$include_paths = explode(':', get_include_path());

			$filenames = array(
				sprintf('Pinhole/tags/Pinhole%sTag.php', ucfirst($namespace)),
				sprintf('include/tags/%sTag.php', ucfirst($namespace)),
			);

			foreach ($filenames as $filename) {
				foreach ($include_paths as $include_path) {
					$file_path = $include_path.'/'.$filename;
					if (file_exists($file_path)) {
						require_once $file_path;
						break 2;
					}
				}
			}

			foreach ($class_names as $class_name) {
				if (class_exists($class_name)) {
					self::$tag_classes[$namespace] = $class_name;
					$return = $class_name;
					break;
				}
			}
		}

		// load generic machine tag class
		if ($return === null) {
			if (!class_exists('PinholeMachineTag'))
				require_once 'Pinhole/tags/PinholeMachineTag.php';

			$return = 'PinholeMachineTag';
		}

		return $return;
	}

	private function __construct()
	{
	}
}

?>
