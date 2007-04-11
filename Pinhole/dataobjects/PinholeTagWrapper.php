<?php

require_once 'Store/dataobjects/StoreRecordsetWrapper.php';
require_once 'PinholeTag.php';

/**
 * A recordset wrapper class for PinholeTag objects
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @see       PinholeTag
 */
class PinholeTagWrapper extends StoreRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = 'PinholeTag';
	}

	// }}}
}

?>
