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
	 * @var SwatDate
	 */
	public $createdate;

	/**
	 * Whether this tag represents an event or not
	 *
	 * When browsing a single tag that's an event, photos will be ordered
	 * chronologically instead of the default reverse-chronological.
	 *
	 * @var boolean
	 */
	public $event;

	/**
	 * Whether this tag is archived in the admin or not
	 *
	 * Archived tags don't show up in the add-tag tool.
	 *
	 * @var boolean
	 */
	public $archived;

	/**
	 * Whether this tag's photos can be ordered manually
	 *
	 * @var boolean
	 */
	public $order_manually;

	// }}}
	// {{{ public read-only properties

	/**
	 * First modified
	 *
	 * @var SwatDate
	 */
	public $first_modified;

	/**
	 * Last modified
	 *
	 * @var SwatDate
	 */
	public $last_modified;

	/**
	 * Photo count
	 *
	 * @var integer
	 */
	public $photo_count;

	// }}}
	// {{{ public function loadByName()

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
	public function loadByName($name, SiteInstance $instance = null)
	{
		$this->instance = $instance;
		$instance_id = ($this->instance === null) ? null : $this->instance->id;

		$row = null;
		$loaded = false;

		if ($this->table !== null) {
			$sql = sprintf('select * from %s where name = %s
				and instance %s %s',
				$this->table,
				$this->db->quote($name, 'text'),
				SwatDB::equalityOperator($instance_id),
				$this->db->quote($instance_id, 'integer'));

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

		// read-only
		$this->registerDateProperty('first_modified');
		$this->registerDateProperty('last_modified');

		// for efficient loading of tag sets on photo sets
		$this->registerInternalProperty('photo');
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
		if ($this->id === null) {
			$this->name = $this->generateShortname($this->title);
			$this->createdate = new SwatDate();
			$this->createdate->toUTC();
		}

		parent::saveInternal();
	}

	// }}}
	// {{{ protected function generateShortname()

	/**
	 * Generate a shortname
	 *
	 * @param string $text Text to generate the shortname from.
	 * @return string A shortname.
	 */
	protected function generateShortname($text)
	{
		$shortname_base = SwatString::condenseToName($text);
		$count = 1;
		$shortname = $shortname_base;

		while ($this->validateShortname($shortname) === false)
			$shortname = $shortname_base.$count++;

		return $shortname;
	}

	// }}}
	// {{{ protected function validateShortname()

	protected function validateShortname($shortname)
	{
		$valid = true;

		$class_name = SwatDBClassMap::get('PinholeTagDataObject');
		$tag = new $class_name();
		$tag->setDatabase($this->db);

		if ($tag->loadByName($shortname, $this->instance))
			if ($tag->id !== $this->id)
				$valid = false;

		return $valid;
	}

	// }}}
}

?>
