<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A dataobject for the comments displayed with photos
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeComment extends SwatDBDataObject
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
	public $photo;

	/**
	 * 
	 *
	 * @var string
	 */
	public $name;

	/**
	 * 
	 *
	 * @var string
	 */
	public $bodytext;

	/**
	 * default false,
	 *
	 * @var string
	 */
	public $email;

	/**
	 * default false,
	 *
	 * @var string
	 */
	public $url;
	
	/**
	 *
	 *
	 * @var timestamp
	 */
	public $createdate;

	/**
	 *
	 *
	 * @var integer
	 */
	public $rating;
	 
	 /**
	  *
	  *
	  * @var string
	  */
	public $remote_ip;
	
	/**
	 *
	 *
	 * @var boolean
	 */
	public $show;
	  
	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table =	'PinholeComment';
		$this->id_field = 'integer:id';

		$this->registerDateProperty($this->createdate);
	}

	// }}}
}

?>
