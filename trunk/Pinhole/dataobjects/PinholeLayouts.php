<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A dataobject for layouts used on the front pages
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeLayouts extends SwatDBDataObject
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
	public $title;

	/**
	 * 
	 *
	 * @var string
	 */
	public $pagewidth;

	/**
	 * 
	 *
	 * @var string
	 */
	public $sidebarposition;

	/**
	 * not null default 0,
	 *
	 * @var integer
	 */
	public $sidebarwidth;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'PinholeLayouts';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
