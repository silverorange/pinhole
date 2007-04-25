<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'PinholeTag.php';

/**
 * A recordset wrapper class for PinholeTag objects
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholeTag
 */
class PinholeTagWrapper extends SwatDBRecordsetWrapper
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
