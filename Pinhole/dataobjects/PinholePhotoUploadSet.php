<?php

/**
 * A simple dataobject for grouping uploaded photos
 *
 * @package   Pinhole
 * @copyright 2005-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoUploadSet extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier for this set
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The date this set was created
	 *
	 * @var SwatDate
	 */
	public $createdate;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'PinholePhotoUploadSet';
		$this->id_field = 'integer:id';
		$this->registerDateProperty('createdate');
	}

	// }}}
}

?>
