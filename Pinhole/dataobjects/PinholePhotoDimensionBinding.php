<?php

require_once 'Site/dataobjects/SiteImageDimensionBinding.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';

/**
 * A dataobject class for photo-dimension bindings
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoDimensionBinding extends SiteImageDimensionBinding
{
	// {{{ public properties

	/**
	 * Photo Id
	 *
	 * This is not an internal property since alternative effiecient methods
	 * are used to load dimensions and dimension bindings.
	 *
	 * @var integer
	 */
	public $photo;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->table = 'PinholePhotoDimensionBinding';
		$this->image_field = 'photo';
	}

	// }}}
}

?>
