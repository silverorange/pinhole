<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'PinholeDimension.php';

/**
 * A recordset wrapper class for PinholeDimension objects
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholeDimension
 */
class PinholeDimensionWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			$this->class_map->resolveClass('PinholeDimension');
	}

	// }}}
}

?>
