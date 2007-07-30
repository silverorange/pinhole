<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'PinholePhotoDimensionBinding.php';

/**
 * A recordset wrapper class for PinholePhotoDimensionBinding objects
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholePhotoDimensionBinding
 */
class PinholePhotoDimensionBindingWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			SwatDBClassMap::get('PinholePhotoDimensionBinding');
	}

	// }}}
}

?>
