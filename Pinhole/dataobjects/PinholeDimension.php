<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A dataobject class for dimensions
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeDimension extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * A short textual identifier for this dimension
	 *
	 * This identifier is designed to be used in URL's and must be unique.
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * User visible title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Maximum width in pixels
	 *
	 * @var integer
	 */
	public $max_width;

	/**
	 * Maximum height in pixels
	 *
	 * @var integer
	 */
	public $max_height;

	/**
	 * 
	 *
	 * @var boolean
	 */
	public $crop_to_max;

	/**
	 * Strip extra data
	 *
	 * Whether or not to strip any extraneous data from the image such as
	 * exif data and embedded thumbnail images.
	 *
	 * @var boolean
	 */
	public $strip;

	/**
	 * Publically accesible
	 *
	 * If true, the image will be stored in a web-accessible directory, if
	 * false, the image will be loaded through a file-loader. 
	 *
	 * @var boolean
	 */
	public $publicly_accessible;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = SwatDBClassMap::get('PinholeDimension');
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
