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
	// {{{ public function loadFromShortname()

	/**
	 * Loads a instance by its shortname
	 *
	 * @param string $shortname the shortname of the instance to load.
	 */
	public function loadFromName($shortname)
	{
		$row = null;

		if ($this->table !== null) {
			$sql = sprintf('select * from %s where shortname = %s',
				$this->table,
				$this->db->quote($shortname, 'text'));

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
		$this->table = 'PinholeInstance';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
