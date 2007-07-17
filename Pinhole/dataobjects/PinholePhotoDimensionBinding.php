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
	 * not null,
	 *
	 * @var integer
	 */
	public $width;

	/**
	 * not null,
	 *
	 * @var integer
	 */
	public $height;

	// }}}
	// {{{ public function getURI()

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

	public function getPath($base_dir = '')
	{
		if ($this->dimension->publicly_accessible)
			$path = '../www/images/photos/%s/';
		else
			$path = '../private-photos/%s/';

		$path = sprintf($path,
			$this->dimension->shortname);

		return sprintf('%s/%s.%s',
			realpath($base_dir.$path),
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
