<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Pinhole/dataobjects/PinholeTagDataObject.php';
require_once 'Pinhole/tags/PinholeTag.php';

/**
 * A recordset wrapper class for PinholeTag objects
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholeTag
 * @see       PinholeTagDataObject
 */
class PinholeTagWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->index_field = 'name';
	}

	// }}}
	// {{{ protected function instantiateRowWrapperObject()

	protected function instantiateRowWrapperObject($row)
	{
		$data_object = new PinholeTagDataObject($row);
		return new PinholeTag($data_object);
	}

	// }}}
}

?>
