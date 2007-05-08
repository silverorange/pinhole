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
	// {{{ constants

	const STATUS_ENABLED = 0;
	const STATUS_ARCHIVED = 1;
	const STATUS_DISABLED = 2;

	// }}}
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

	/**
	 * status
	 *
	 * @var integer
	 */
	public $status;

	// }}}
	// {{{ public static function getStatuses()

	public function getStatuses()
	{
		return array(
			self::STATUS_ENABLED  => Pinhole::_('Visible on Site & Admin Photo Tools'),
			self::STATUS_ARCHIVED => Pinhole::_('Visible on Site & Archived in Admin Photo Tools'),
			self::STATUS_DISABLED => Pinhole::_('Not Visible on Site or Admin Photo Tools'),
		);
	}

	// }}}
	// {{{ public static function getStatusTitle()

	public static function getStatusTitle($status)
	{
		$statuses = self::getStatuses();
		return $statuses[$status];
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('parent', 'PinholeTag');

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
