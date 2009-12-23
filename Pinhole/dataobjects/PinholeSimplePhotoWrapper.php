<?php

require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';

/**
 * A simple recordset wrapper class for PinholePhoto objects that doesn't load
 * image dimension data. Don't use this if you want to display photos.
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholePhotoWrapper
 */
class PinholeSimplePhotoWrapper extends PinholePhotoWrapper
{
	// {{{ public function __construct()

	/**
	 * Creates a new recordset wrapper
	 *
	 * @param MDB2_Result $recordset optional. The MDB2 recordset to wrap.
	 */
	public function __construct($recordset = null)
	{
		$this->binding_table = 'PinholePhotoDimensionBinding';
		$this->binding_table_image_field = 'photo';

		SwatDBRecordsetWrapper::__construct($recordset);
	}

	// }}}
}

?>
