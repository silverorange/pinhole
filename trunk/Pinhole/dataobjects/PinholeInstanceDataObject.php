<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'PinholeInstance.php';

/**
 * A specialized data-object for objects that are instance-specific
 *
 * @package   Pinhole
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholeInstance
 */
class PinholeInstanceDataObject extends SwatDBDataObject
{
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('instance',
			SwatDBClassMap::get('PinholeInstance'));
	}

	// }}}

	// database loading and saving
	// {{{ public function setInstance()

	/**
	 * Sets the instance for this data-object
	 *
	 * If this data-object has an instance set, subsequent calls to
	 * {@link PinholeInstanceDataObject::load()} will only load if this object
	 * belongs to the specified instance.
	 *
	 * @param PinholeInstance $instance the instance for this data-object.
	 */
	public function setInstance(PinholeInstance $instance)
	{
		$this->instance = $instance;
	}

	// }}}
	// {{{ protected function loadInternal()

	/**
	 * Loads this data-object's properties from the database given an id
	 *
	 * If this object is associeted with an instance through the
	 * {@link PinholeInstanceDataObject::setInstance()} method, load internal
	 * will only load rows matching the specified instance.
	 *
	 * @param mixed $id the id of the database row to set this object's
	 *                       properties with.
	 *
	 * @return mixed this object's data row or null is no such row exists.
	 */
	protected function loadInternal($id)
	{
		$row = null;

		if ($this->instance === null) {
			$row = parent::loadInternal($id);
		} else {
			if ($this->table !== null && $this->id_field !== null) {
				$id_field = new SwatDBField($this->id_field, 'integer');
				$sql = 'select * from %s where %s = %s and instance = %s';

				$sql = sprintf($sql,
					$this->table,
					$id_field->name,
					$this->db->quote($id, $id_field->type),
					$this->db->quote($this->instance->id, 'integer'));

				$rs = SwatDB::query($this->db, $sql, null);
				$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
			}
		}

		return $row;
	}

	// }}}
	// {{{ protected function saveInternal()

	/**
	 * Saves this object to the database
	 *
	 * Only modified properties are updated.
	 */
	protected function saveInternal()
	{
		if ($this->instance === null) {
			trigger_error(
				sprintf('No instance defined for %s', get_class($this)),
				E_USER_NOTICE);

			return;
		}

		parent::saveInternal();
	}

	// }}}
	// {{{ protected function deleteInternal()

	/**
	 * Deletes this object from the database
	 */
	protected function deleteInternal()
	{
		if ($this->instance === null) {
			trigger_error(
				sprintf('No instance defined for %s', get_class($this)),
				E_USER_NOTICE);

			return;
		}

		if ($this->table === null || $this->id_field === null)
			return;

		$id_field = new SwatDBField($this->id_field, 'integer');

		if (!property_exists($this, $id_field->name))
			return;

		$id_ref = $id_field->name;
		$id = $this->$id_ref;

		if ($id !== null) {
			$sql = sprintf('delete from %s
				where %s = %s and instance = %s',
				$this->table, $id_field->name, $id,
				$this->db->quote($this->instance->id, 'integer'));

			SwatDB::exec($this->db, $sql);
		}
	}

	// }}}
}

?>
