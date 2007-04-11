<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A dataobject class for photographers
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotographer extends SwatDBDataObject
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
	public $fullname;

	/**
	 * 
	 *
	 * @var string
	 */
	public $bodytext;

	/**
	 * not null default true,
	 *
	 * @var boolean
	 */
	public $enabled;

	/**
	 * not null default false,
	 *
	 * @var boolean
	 */
	public $archived;

	/**
	 * 
	 *
	 * @var Date
	 */
	public $createdate;

	// }}}
}

?>
