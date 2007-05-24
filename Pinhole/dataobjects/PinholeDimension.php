<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A dataobject class for dimensions
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeDimension extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * 
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * 
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * 
	 *
	 * @var string
	 */
	public $title;

	/**
	 * 
	 *
	 * @var integer
	 */
	public $max_width;

	/**
	 * 
	 *
	 * @var integer
	 */
	public $max_height;

	/**
	 * default false,
	 *
	 * @var boolean
	 */
	public $crop_to_max;

	/**
	 * default false,
	 *
	 * @var boolean
	 */
	public $strip;

	/**
	 * default false,
	 *
	 * @var boolean
	 */
	public $publicly_accessible;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = $this->class_map->resolveClass('PinholeDimension');
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
