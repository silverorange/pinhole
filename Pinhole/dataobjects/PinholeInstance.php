<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A dataobject class for site instances
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeInstance extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The shortname of this instance
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * The title of this instance
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Whether or not this instance is enabled
	 *
	 * @var boolean
	 */
	public $enabled;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'PinholeInstance';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
