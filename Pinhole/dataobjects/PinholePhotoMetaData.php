<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A dataobject class for photo meta data
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoMetaData extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * not null,
	 *
	 * @var string
	 */
	public $title;

	/**
	 * not null,
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * not null,
	 *
	 * @var string
	 */
	public $value;

	/**
	 * not null,
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

		$this->registerInternalProperty('metadata',
			$this->class_map->resolveClass('PinholeMetaData'));
	}

	// }}}
}

?>
