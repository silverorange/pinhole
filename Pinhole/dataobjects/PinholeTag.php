<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A dataobject class for tags
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeTag extends SwatDBDataObject
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
	 * @var integer
	 */
	public $parent;

	/**
	 * 
	 *
	 * @var string
	 */
	public $shortname;

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
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'PinholeTag';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function loadPath()

	/**
	 * Loads the URL fragment of this tag
	 *
	 * If the path was part of the initial query to load this path, that
	 * value is returned. Otherwise, a separate query gets the path of this
	 * path. If you are calling this method frequently during a single
	 * request, it is more efficient to include the path in the initial
	 * path query.
	 */
	protected function loadPath()
	{
		$path = '';

		if ($this->hasInternalValue('path') &&
			$this->getInternameValue('path') !== null) {
			$path = $this->getInternalValue('path');
		} else {
			$sql = sprintf('select getPinholeTagPath(%s)',
				$this->db->quote($this->id, 'integer'));

			$path = SwatDB::queryOne($this->db, $sql);
		}

		return $path;
	}

	// }}}
}

?>
