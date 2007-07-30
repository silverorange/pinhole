<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Swat/SwatDate.php';

/**
 * An internal dataobject class for tags
 *
 * This class is intended to be used internally with PinholeTag. Ideally this
 * would be a private inner class of PinholeTag.
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeTagDataObject extends SwatDBDataObject
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
	public $name;

	/**
	 * 
	 *
	 * @var string
	 */
	public $title;

	/**
	 * 
	 *
	 * @var Date
	 */
	public $createdate;

	// }}}
	// {{{ public function loadFromShortname()

	/**
	 * Loads a tag data-object by its name
	 *
	 * @param string $name the name of the tag data-object to load.
	 */
	public function loadFromName($name)
	{
		$row = null;

		if ($this->table !== null) {
			$sql = sprintf('select * from %s where name = %s',
				$this->table,
				$this->db->quote($name, 'text'));

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row === null)
			return false;

		$this->initFromRow($row);
		$this->generatePropertyHashes();
		return true;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'PinholeTag';
		$this->id_field = 'integer:id';
		$this->registerDateProperty('createdate');
	}

	// }}}
}

?>
