<?php

require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'SwatDB/SwatDBRecordable.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class PinholeAbstractTag implements SwatDBRecordable
{
	/**
	 * @var MDB2_Driver_Common
	 */
	protected $db;

	/**
	 * @var PinholePhotoWrapper
	 */
	protected $photos_cache;

	abstract public function parse($string, MDB2_Driver_Common $db);

	abstract public function getTitle();

	abstract public function __toString();

	public function getWhereClause()
	{
		return 'false';
	}

	public function getJoinClauses()
	{
		return array();
	}

	public function getRange()
	{
		return null;
	}

	abstract public function applyToPhoto(PinholePhoto $photo);

	abstract public function appliesToPhoto(PinholePhoto $photo);

	public function getPhotos(SwatDBRange $range = null)
	{
		if ($this->photos_cache === null) {
			$sql = sprintf('select * from PinholePhoto %s where %s',
				implode("\n", $this->getJoinClauses()),
				$this->getWhereClause());

			if ($range !== null)
				$this->db->setRange($range->getLimit(), $range->getOffset());

			$wrapper = SwatDBClassMap::get('PinholePhotoWrapper');
			$this->photos_cache = SwatDB::query($this->db, $sql, $wrapper);
		}

		return $this->photos_cache;
	}

	public function getPhotoCount()
	{
		$sql = sprintf('select count(id) from PinholePhoto %s where %s',
			implode("\n", $this->getJoinClauses()),
			$this->getWhereClause());

		return SwatDB::queryOne($this->db, $sql);
	}

	public function setDatabase(MDB2_Driver_Common $db)
	{
		$this->db = $db;
	}

	public function save()
	{
	}

	public function load($data)
	{
	}

	public function delete()
	{
	}

	public function isModified()
	{
		return false;
	}
}

?>
