<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 *
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeDimension extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * 
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * 
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * 
	 *
	 * @var string
	 */
	public $title;

	/**
	 * 
	 *
	 * @var integer
	 */
	public $max_width;

	/**
	 * 
	 *
	 * @var integer
	 */
	public $max_height;

	/**
	 * default false,
	 *
	 * @var boolean
	 */
	public $crop_to_max;

	// }}}
}

?>
