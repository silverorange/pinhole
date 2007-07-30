<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A dataobject class for photo-dimension bindings
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoDimensionBinding extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Image width in pixels
	 *
	 * @var integer
	 */
	public $width;

	/**
	 * Image height in pixels
	 *
	 * @var integer
	 */
	public $height;

	// }}}
	// {{{ public function getURI()

	/**
	 * Gets the URI for the current photo and dimension
	 *
	 * @return string the URI for loading the current photo.
	 */
	public function getURI()
	{
		if ($this->dimension->publicly_accessible)
			return sprintf('images/photos/%s/%s.jpg',
				$this->dimension->shortname,
				$this->photo->filename);
		else
			return sprintf('loadphoto/%s/%s.jpg',
				$this->dimension->shortname,
				$this->photo->filename);
	}

	// }}}
	// {{{ public function getPath()

	/**
	 * Gets the absolute file path for the current photo and dimension
	 *
	 * @param string $base_dir optional. The directory to start the path from.
	 *                          If not specified, defaults to the web root.
	 *
	 * @return string an absolute path to the current photo.
	 */
	public function getPath($base_dir = '.')
	{
		if ($this->dimension->publicly_accessible)
			$path = 'images/photos/%s/';
		else
			$path = '../private-photos/%s/';

		$path = sprintf($path,
			$this->dimension->shortname);

		return sprintf('%s/%s.%s',
			realpath($base_dir.'/'.$path),
			$this->photo->filename,
			'jpg');
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->registerInternalProperty('dimension',
			SwatDBClassMap::get('PinholeDimension'));

		$this->registerInternalProperty('photo',
			SwatDBClassMap::get('PinholePhoto'));
	}

	// }}}
}

?>
