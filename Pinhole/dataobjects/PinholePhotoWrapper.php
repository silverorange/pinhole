<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/dataobjects/SiteImageWrapper.php';
require_once 'PinholePhoto.php';

/**
 * A recordset wrapper class for PinholePhoto objects
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholePhoto
 */
class PinholePhotoWrapper extends SiteImageWrapper
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

		parent::__construct($recordset);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('PinholePhoto');
	}

	// }}}
	// {{{ protected function getImageDimensionBindingWrapperClassName()

	protected function getImageDimensionBindingWrapperClassName()
	{
		return SwatDBClassMap::get('PinholePhotoDimensionBindingWrapper');
	}

	// }}}
}

?>
