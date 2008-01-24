<?php

require_once 'Site/dataobjects/SiteImageDimensionBinding.php';

/**
 * A dataobject class for photo-dimension bindings
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoDimensionBinding extends SiteImageDimensionBinding
{
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'PinholePhotoDimensionBinding';

		parent::init();
	}

	// }}}
}

?>
