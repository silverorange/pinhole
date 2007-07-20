<?php

require_once 'Pinhole/tags/PinholeAbstractTag.php';
require_once 'Pinhole/dataobjects/PinholeTagDataObject.php';
require_once 'MDB2.php';

/**
 * Tag factory
 *
 * The tag factory is responsible for creating tag objects from tag strings. It
 * reads the string, determines the appropriate tag class to instantiate and
 * instantiates an instance of the class. The main interface used on the tag
 * factory is {@link PinholeTagFactory::get()}.
 *
 * The tag factory will attempt to find and require class definition files for
 * tags of unknown classes. The first location the tag factory looks in is
 * 'Pinhole/tags/'. If the class definition is still not found, the tag factory
 * looks in 'include/tags/' (site-level tag definitions).
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeTagFactory
{
	// {{{ private static properties

	/**
	 * Cache of known tag classes indexed by namespace
	 *
	 * @var array
	 */
	private static $tag_classes = array();

	/**
	 * Default database connection to use when creating new tag objects
	 *
	 * @var MDB2_Driver_Common
	 *
	 * @see PinholeTagFactory::setDefaultDatabase()
	 */
	private static $default_database;

	// }}}
	// {{{ public static function get()

	/**
	 * Parses a tag from a tag string and returns an appropriate tag object
	 *
	 * @param string $string the tag string to parse.
	 * @param MDB2_Driver_Common $db optional. The database connection to use
	 *                                for the parsed tag. If not specified,
	 *                                the default database specified by the
	 *                                tag factory is used.
	 *
	 * @return PinholeAbstractTag the parsed tag object or null if the given
	 *                             string could not be parsed.
	 */
	public static function get($string, MDB2_Driver_Common $db = null)
	{
		if ($db === null &&
			self::$default_database instanceof MDB2_Driver_Common) {
			$db = self::$default_database;
		}

		// get tag string namespace
		$machine_tag_pattern = '/^([a-z]+)\.[a-z]+=[a-zA-Z0-9-\+ ]*$/';
		$matches = array();
		if (preg_match($machine_tag_pattern, $string, $matches) == 1)
			$namespace = $matches[1];
		else
			$namespace = null;

		// get tag class
		$tag_class = self::getTagClass($namespace);

		// create and parse tag
		$tag = new $tag_class();
		$valid = $tag->parse($string, $db);

		return ($valid) ? $tag : null;
	}

	// }}}
	// {{{ public static function setDefaultDatabase()

	/**
	 * Sets the default database used by the tag factory
	 *
	 * @param MDB2_Driver_Common the default database used by the tag factory.
	 */
	public static function setDefaultDatabase(MDB2_Driver_Common $db)
	{
		self::$default_database = $db;
	}

	// }}}
	// {{{ private static function getTagClass()

	/**
	 * Gets the tag class used by a particular tag namespace
	 *
	 * If an unknown tag namespace is used, thie method attempts to require
	 * the correct class definition file.
	 *
	 * @param string $namespace the namespace of the tag class to get.
	 *
	 * @return string the tag class name for the specified namespace.
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
					// cache tag class
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
					// cache tag class
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

	// }}}
	// {{{ private function __construct()

	/**
	 * Private constructor to prevent instantiation of tag factory
	 *
	 * All useful tag factory methods are static.
	 */
	private function __construct()
	{
	}

	// }}}
}

?>
