<?php

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
 * @todo      Add a caching mechanism so getting the same tag multiple times
 *            does not create multiple objects.
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

	/**
	 * Default site instance to use when creating new tag objects
	 *
	 * @var SiteInstance
	 *
	 * @see PinholeTagFactory::setDefaultInstance()
	 */
	private static $default_instance;

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
	 * @param SiteInstance $instance optional. The site instance to use for the
	 *                                the parsed tag. If not specified, the
	 *                                default instance specified by the tag
	 *                                factory is used.
	 *
	 * @return PinholeAbstractTag the parsed tag object or null if the given
	 *                             string could not be parsed.
	 */
	public static function get(
		$string,
		MDB2_Driver_Common $db = null,
		SiteInstance $instance = null
	) {
		if ($db === null &&
			self::$default_database instanceof MDB2_Driver_Common) {
			$db = self::$default_database;
		}

		if ($instance === null &&
			self::$default_instance instanceof SiteInstance) {
			$instance = self::$default_instance;
		}

		// get tag string namespace
		$machine_tag_pattern = '/^([a-z]+)\.[a-z]+=.*$/';
		$matches = array();
		if (preg_match($machine_tag_pattern, $string, $matches) == 1)
			$namespace = $matches[1];
		else
			$namespace = null;

		// get tag class
		$tag_class = self::getTagClass($namespace);

		// create and parse tag
		$tag = new $tag_class();
		$valid = $tag->parse($string, $db, $instance);

		return ($valid) ? $tag : null;
	}

	// }}}
	// {{{ public static function setDefaultInstance()

	/**
	 * Sets the default site instance used by the tag factory
	 *
	 * @param SiteInstance $instance the default site instance used by the tag
	 *                                factory.
	 */
	public static function setDefaultInstance(SiteInstance $instance)
	{
		self::$default_instance = $instance;
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
			foreach ($class_names as $class_name) {
				if (class_exists($class_name)) {
					// cache tag class
					self::$tag_classes[$namespace] = $class_name;
					$return = $class_name;
					break;
				}
			}
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
