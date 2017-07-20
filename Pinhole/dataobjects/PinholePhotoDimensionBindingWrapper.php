<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/dataobjects/SiteImageDimensionBindingWrapper.php';
require_once 'PinholePhotoDimensionBinding.php';

/**
 * A recordset wrapper class for PinholePhotoDimensionBinding objects
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholePhotoDimensionBinding
 */
class PinholePhotoDimensionBindingWrapper extends
 SiteImageDimensionBindingWrapper
{
	// {{{ protected function getImageDimensionBindingClassName()

	protected function getImageDimensionBindingClassName()
	{
		return SwatDBClassMap::get('PinholePhotoDimensionBinding');
	}

	// }}}
}

?>
