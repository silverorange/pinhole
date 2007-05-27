<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Swat/SwatDate.php';

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
	// {{{ private properties

	/**
	 * Photo count
	 *
	 * @var integer
	 */
	private $photo_count;

	/**
	 * Last updated
	 *
	 * @var Date
	 */
	private $last_updated;

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
	// {{{ public function getWhereClause()

	public function getWhereClause()
	{
		return sprintf('PinholePhoto.id in
			(select PinholePhotoTagBinding.photo
			from PinholePhotoTagBinding
			where PinholePhotoTagBinding.tag = %s)',
			$this->db->quote($this->id, 'integer'));
	}

	// }}}
	// {{{ public function getJoinClause()

	public function getJoinClause()
	{
		return '';
	}

	// }}}
	// {{{ public function getPath()

	public function getPath()
	{
		return $this->shortname;
	}

	// }}}
	// {{{ public function getTitle()

	public function getTitle()
	{
		return $this->title;
	}

	// }}}
	// {{{ public function setLastUpdated()

	public function setLastUpdated(SwatDate $date)
	{
		$this->last_updated = $date;
	}

	// }}}
	// {{{ public function getLastUpdated()

	public function getLastUpdated()
	{
		if ($this->last_updated === null) {
			$sql = sprintf('select max(PinholePhoto.photo_date)
				from PinholePhoto
				inner join PinholePhotoTagBinding on
					PinholePhotoTagBinding.photo =
					PinholePhoto.id
				where PinholePhotoTagBinding.tag = %s',
				$this->db->quote($this->id, 'integer'));

			$date = SwatDB::queryOne($this->db, $sql);

			return ($date === null) ? null : new SwatDate($date);
		} else {
			return $this->last_updated;
		}
	}

	// }}}
	// {{{ public function setPhotoCount()

	public function setPhotoCount($photo_count)
	{
		$this->photo_count = $photo_count;
	}

	// }}}
	// {{{ public function getPhotoCount()

	public function getPhotoCount()
	{
		if ($this->photo_count === null) {
			$sql = sprintf('select count(PinholePhoto.id)
				from PinholePhoto
				inner join PinholePhotoTagBinding on
					PinholePhotoTagBinding.photo =
					PinholePhoto.id
				where PinholePhotoTagBinding.tag = %s',
				$this->db->quote($this->id, 'integer'));

			return SwatDB::queryOne($this->db, $sql);
		} else {
			return $this->photo_count;
		}
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('parent',
			$this->class_map->resolveClass('PinholeTag'));

		$this->table = $this->class_map->resolveClass('PinholeTag');
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
			$this->getInternalValue('path') !== null) {
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
