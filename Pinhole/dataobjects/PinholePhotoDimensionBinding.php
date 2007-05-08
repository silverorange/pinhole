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
		return sprintf('images/photos/%s/%s.jpg',
			$this->dimension->shortname,
			$this->photo->id);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->registerInternalProperty('dimension', 'PinholeDimension');
		$this->registerInternalProperty('photo', 'PinholePhoto');
	}

	// }}}
}

?>
