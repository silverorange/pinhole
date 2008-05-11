<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteInstance.php';

/**
 * A specialized data-object for objects that are instance-specific
 *
 * @package   Pinhole
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       SiteInstance
 */
class PinholeInstanceDataObject extends SwatDBDataObject
{
	// {{{ public function setInstance()

	/**
	 * Sets the instance for this data object
	 *
	 * param SiteInstance $instance The instance for this data-object
	 */
	public function setInstance(SiteInstance $instance = null)
	{
		$this->instance = $instance;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('instance',
			SwatDBClassMap::get('SiteInstance'));
	}

	// }}}

	// database loading and saving
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
	// {{{ protected function deleteInternal()

	/**
	 * Deletes this object from the database
	 */
	protected function deleteInternal()
	{
		if ($this->instance === null)
			parent::deleteInternal();

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
