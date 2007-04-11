<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 *
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholePhoto extends SwatDBDataObject
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
	public $description;

	/**
	 * 
	 *
	 * @var Date
	 */
	public $upload_date;

	/**
	 * 
	 *
	 * @var string
	 */
	public $original_filename;

	/**
	 * 
	 *
	 * @var string
	 */
	public $exif;

	/**
	 * references PinholePhotographer(id),
	 *
	 * @var integer
	 */
	public $photographer;

	/**
	 * 
	 *
	 * @var Date
	 */
	public $photo_date;

	/**
	 * not null default 0,
	 *
	 * @var integer
	 */
	public $photo_date_parts;

	/**
	 * 
	 *
	 * @var Date
	 */
	public $publish_date;

	/**
	 * not null default false,
	 *
	 * @var boolean
	 */
	public $published;

	// }}}
}

?>
