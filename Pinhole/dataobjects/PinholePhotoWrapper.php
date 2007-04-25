<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'PinholePhoto.php';

/**
 * A recordset wrapper class for PinholePhoto objects
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholePhoto
 */
class PinholePhotoWrapper extends SwatDBRecordsetWrapper
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
