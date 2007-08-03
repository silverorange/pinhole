<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A dataobject class for photo meta data
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoMetaDataBinding extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * User visible title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * A short textual identifier for this meta data
	 *
	 * This value should be read-only and is created from the embeded meta
	 * data in photos.
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * User visible value
	 *
	 * @var string
	 */
	public $value;

	/**
	 * Whether or not this meta data can be browsed as a machine tag.
	 *
	 * Allows users to browse all photos that share the same value as this
	 * meta data. Values with near-unique values like dates should not be
	 * browsed as machine tags.
	 *
	 * @var boolean
	 */
	public $machine_tag;

	// }}}
	// {{{ public function getURI()

	/**
	 * Gets the URI snippet
	 *
	 * @return string a URI tag snippet for this meta data binding.
	 */
	public function getURI()
	{
		$value = self::escapeValue($this->value);
		$value = rawurlencode($value);

		$uri = sprintf('meta.%s=%s',
			$this->shortname,
			$value);

		return $uri;
	}

	// }}}
	// {{{ public static function escapeValue()

	/**
	 * Escapes a meta data value for inclusion in a tag list URI
	 *
	 * @param string $string the value to be escaped.
	 *
	 * @return string the escaped value.
	 */
	public static function escapeValue($string)
	{
		$string = str_replace('|', '||', $string);
		$string = str_replace('/', '|s', $string);

		return $string;
	}

	// }}}
	// {{{ public static function unescapeValue()

	/**
	 * Unescapes a meta data value after inclusion in a tag list URI
	 *
	 * @param string $string the value to be unescaped.
	 *
	 * @return string the unescaped value.
	 */
	public static function unescapeValue($string)
	{
		$string = str_replace('|s', '/', $string);
		$string = str_replace('||', '|', $string);

		return $string;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->table = 'PinholePhotoMetaDataBinding';

		$this->registerInternalProperty('photo',
			SwatDBClassMap::get('PinholePhoto'));

		//$this->registerInternalProperty('metadata',
		//	$this->class_map->resolveClass('PinholeMetaData'));
	}

	// }}}
}

?>
