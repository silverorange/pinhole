<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'PinholeDimension.php';

/**
 * A recordset wrapper class for PinholeDimension objects
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholeDimension
 */
class PinholeDimensionWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = 'PinholeDimension';
	}

	// }}}
}

?>
