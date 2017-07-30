<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/dataobjects/SiteImageWrapper.php';
require_once 'PinholePhoto.php';

/**
 * A recordset wrapper class for PinholePhoto objects
 *
 * @package   Pinhole
 * @copyright 2007-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholePhoto
 */
class PinholePhotoWrapper extends SiteImageWrapper
{
	// {{{ public function __construct()

	public function __construct(
		MDB2_Result_Common $rs = null,
		array $options = array()
	) {
		$this->binding_table = 'PinholePhotoDimensionBinding';
		$this->binding_table_image_field = 'photo';

		parent::__construct($rs, $options);
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
