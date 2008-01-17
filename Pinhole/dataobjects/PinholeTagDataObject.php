<?php

require_once 'Pinhole/dataobjects/PinholeInstanceDataObject.php';
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
class PinholeTagDataObject extends PinholeInstanceDataObject
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
	// {{{ public function loadFromName()

	/**
	 * Loads a tag data-object by its name
	 *
	 * An instance is required to load this object by name since tag names are
	 * not required to be unique across site instances.
	 *
	 * @param string $name the name of the tag data-object to load.
	 * @param SiteInstance $instance the site instance of the tag data-object
	 *                                   to load.
	 *
	 * @return boolean true if this tag data-object was loaded and false if it
	 *                  could not be loaded.
	 */
	public function loadFromName($name, SiteInstance $instance)
	{
		$this->setInstance($instance);

		$row = null;
		$loaded = false;

		if ($this->table !== null) {
			$sql = sprintf('select * from %s where name = %s
				and instance = %s',
				$this->table,
				$this->db->quote($name, 'text'),
				$this->db->quote($this->instance->id, 'integer'));

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row !== null) {
			$this->initFromRow($row);
			$this->generatePropertyHashes();
			$loaded = true;
		}

		return $loaded;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->table = 'PinholeTag';
		$this->id_field = 'integer:id';
		$this->registerDateProperty('createdate');
	}

	// }}}
}

?>
