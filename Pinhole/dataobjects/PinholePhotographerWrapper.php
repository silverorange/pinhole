<?php

/**
 * A recordset wrapper class for PinholePhotographer objects
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholePhotographer
 */
class PinholePhotographerWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('PinholePhotographer');
	}

	// }}}
}

?>
