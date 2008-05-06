<?php

require_once 'Site/dataobjects/SiteImageSet.php';
require_once 'Site/dataobjects/SiteInstance.php';
require_once 'Pinhole/dataobjects/PinholeImageDimensionWrapper.php';

/**
 * A dataobject class for image sets with instance
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeImageSet extends SiteImageSet
{
	// {{{ public function loadByShortname()

	/**
	 * Loads a set from the database with a shortname and instance
	 *
	 * @param string $shortname the shortname of the set
	 *
	 * @return boolean true if a set was successfully loaded and false if
	 *                  no set was found at the specified shortname.
	 */
	public function loadByShortname($shortname)
	{
		$this->checkDB();

		$instance_id = ($this->instance === null) ? null : $this->instance->id;

		$found = false;
		$sql = 'select * from %s where shortname = %s and instance %s %s';

		$sql = sprintf($sql,
			$this->table,
			$this->db->quote($shortname, 'text'),
			SwatDB::equalityOperator($instance_id),
			$this->db->quote($instance_id, 'integer'));

		$row = SwatDB::queryRow($this->db, $sql);

		if ($row !== null) {
			$this->initFromRow($row);
			$this->generatePropertyHashes();
			$found = true;
		}

		return $found;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->registerInternalProperty('instance',
			SwatDBClassMap::get('SiteInstance'));
	}

	// }}}
	// {{{ protected function getImageDimensionClassName()

	protected function getImageDimensionWrapperClassName()
	{
		return SwatDBClassMap::get('PinholeImageDimensionWrapper');
	}

	// }}}
}

?>
