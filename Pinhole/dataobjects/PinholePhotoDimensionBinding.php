<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 *
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PineholePhotoDimensionBinding extends SwatDBDataObject
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
