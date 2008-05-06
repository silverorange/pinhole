<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Pinhole/dataobjects/PinholeImageDimension.php';

/**
 * A recordset wrapper class for PinholeImageDimension objects
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholeImageDimension
 */
class PinholeImageDimensionWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('PinholeImageDimension');
	}

	// }}}
}

?>
