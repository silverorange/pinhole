<?php

require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';

/**
 * A simple recordset wrapper class for PinholePhoto objects that doesn't load
 * image dimension data. Don't use this if you want to display photos.
 *
 * This is deprecated.
 *
 * @package    Pinhole
 * @copyright  2009-2013 silverorange
 * @license    http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see        PinholePhotoWrapper
 * @deprecated Use {@link PinholePhotoWrapper} with the <kbd>lazy_load</kbd>
 *             option.
 */
class PinholeSimplePhotoWrapper extends PinholePhotoWrapper
{
	// {{{ public function __construct()

	public function __construct(
		MDB2_Result_Common $rs = null,
		array $options = array()
	) {
		$options = array_merge(
			$options,
			array('lazy_load', true)
		);

		parent::__construct($rs, $options);
	}

	// }}}
}

?>
