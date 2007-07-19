<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A dataobject for layouts used on the front pages
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
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
		$this->table =	SwatDBClassMap::get('PinholeLayouts');
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>

