<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'PinholeInstance.php';

/**
 * A recordset wrapper class for PinholeInstance objects
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholeInstance
 */
class PinholeInstanceWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('PinholeInstance');
		$this->index_field = 'id';
	}

	// }}}
}

?>
