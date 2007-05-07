<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A dataobject class for photo-dimension bindings
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoDimensionBinding extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * not null references PinholePhoto(id) on delete cascade,
	 *
	 * @var integer
	 */
	public $photo;

	/**
	 * not null references PinholeDimension(id) on delete cascade,
	 *
	 * @var integer
	 */
	public $dimension;

	/**
	 * not null,
	 *
	 * @var integer
	 */
	public $width;

	/**
	 * not null,
	 *
	 * @var integer
	 */
	public $height;

	// }}}
}

?>
