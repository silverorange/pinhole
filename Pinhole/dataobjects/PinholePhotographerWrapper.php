<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'PinholePhotographer.php';

/**
 * A recordset wrapper class for PinholePhotographer objects
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @see       PinholePhotographer
 */
class PinholePhotographerWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = 'PinholePhotographer';
	}

	// }}}
}

?>
