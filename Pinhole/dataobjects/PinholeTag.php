<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 *
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeTag extends SwatDBDataObject
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
	 * @var integer
	 */
	public $parent;

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
	 * @var Date
	 */
	public $createdate;

	// }}}
}

?>
