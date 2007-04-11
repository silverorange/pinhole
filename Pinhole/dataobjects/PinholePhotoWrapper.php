<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'PinholePhoto.php';

/**
 * A recordset wrapper class for PinholePhoto objects
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @see       PinholePhoto
 */
class PinholePhotoWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = 'PinholePhoto';
	}

	// }}}
}

?>
