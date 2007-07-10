<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * An internal dataobject class for generic machine tags
 *
 * This class is intended to be used internally with PinholeMachineTag. Ideally
 * this would be a private inner class of PinholeMachineTag.
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeMachineTagDataObject extends SwatDBDataObject
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
	public $namespace;

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
	public $value;

	/**
	 * 
	 *
	 * @var Date
	 */
	public $createdate;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'PinholeMachineTag';
		$this->id_field = 'integer:id';
		$this->registerDateProperty('createdate');
	}

	// }}}
	// {{{ public function loadFromFields()

	/**
	 * Loads a machine tag data-object from fields 
	 *
	 * @param string $namespace the namespace of the machine tag data-object to
	 *                           load.
	 * @param string $name the name of the machine tag data-object to load.
	 * @param string $value the value of the machine tag data-object to load.
	 *
	 * @return boolean
	 */
	public function loadFromFields($namespace, $name, $value)
	{
		$row = null;

		if ($this->table !== null) {
			$sql = sprintf('select * from %s
				where namespace = %s and name = %s and value = %s',
				$this->table,
				$this->db->quote($namespace, 'text'),
				$this->db->quote($name, 'text'),
				$this->db->quote($value, 'text'));

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
}

?>
