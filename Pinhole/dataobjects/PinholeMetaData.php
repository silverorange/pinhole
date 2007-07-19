<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A dataobject for the Metadata contained in photos
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeMetaData extends SwatDBDataObject
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
	 * default false,
	 *
	 * @var boolean
	 */
	public $show;

	/**
	 * default false,
	 *
	 * @var boolean
	 */
	public $machine_tag;
	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table =	SwatDBClassMap::get('PinholeMetaData');
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>

