<?php

require_once 'Site/dataobjects/SiteImageDimension.php';

/**
 * A dataobject class for dimensions
 *
 * @package   Pinhole
 * @copyright 2007-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeImageDimension extends SiteImageDimension
{
	// {{{ public constants

	const DIMENSION_ORIGINAL = 'original';

	// }}}
	// {{{ public properties

	/**
	 * Publically accesible
	 *
	 * If true, the image will be stored in a web-accessible directory, if
	 * false, the image will be loaded through a file-loader.
	 *
	 * @var boolean
	 */
	public $publicly_accessible;

	// }}}
}

?>
