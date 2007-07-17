<?php

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
	 * meta data. Values with near-unique values like dates should not be browsed as
	 * machine tags.
	 *
	 * @var boolean
	 */
	public $machine_tag;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->table = 'PinholePhotoMetaDataBinding';

		$this->registerInternalProperty('photo',
			$this->class_map->resolveClass('PinholePhoto'));

		//$this->registerInternalProperty('metadata',
		//	$this->class_map->resolveClass('PinholeMetaData'));
	}

	// }}}
}

?>
