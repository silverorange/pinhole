<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Pinhole/dataobjects/PinholeComment.php';

/**
 * A recordset wrapper class for PinholeComment objects
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @see       PinholeComment
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeCommentWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('PinholeComment');
		$this->index_field = 'id';
	}

	// }}}
}

?>
