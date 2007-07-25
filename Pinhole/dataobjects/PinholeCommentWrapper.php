<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Pinhole/dataobjects/PinholeComment.php';

/**
 * A recordset wrapper class for PinholeComment objects
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeCommentWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = 'PinholeComment';
		$this->index_field = 'id';
	}

	// }}}
}

?>
