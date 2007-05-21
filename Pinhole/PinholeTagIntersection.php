<?php

require_once 'Pinhole/dataobjects/PinholeTagWrapper.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Pinhole/PinholeDateTag.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeTagIntersection
{
	// {{{ private properties

	private $db = null;
	private $tags = array();

	// }}}
	// {{{ public function __construct()

	public function __construct($db)
	{
		$this->db = $db;
	}

	// }}}
	// {{{ public function addTag()

	public function addTag($tag)
	{
		$this->tags[] = $tag;
	}

	// }}}
	// {{{ public function addTagById()

	public function addTagById($id)
	{
		$sql = sprintf('select * from PinholeTag where id = %s',
			$this->db->quote($id, 'integer'));

		// TODO: use classmap
		$tags = SwatDB::query($this->db, $sql, 'PinholeTagWrapper');
		$tag = $tags->getFirst();

		if ($tag !== null)
			$this->addTag($tag);
	}

	// }}}
	// {{{ public function addTagByShortname()

	public function addTagByShortname($shortname)
	{
		$tag = null;

		if (strpos($shortname, '.') === false) {
			$sql = sprintf('select * from PinholeTag where shortname = %s',
				$this->db->quote($shortname, 'text'));

			// TODO: use classmap
			$tags = SwatDB::query($this->db, $sql, 'PinholeTagWrapper');
			$tag = $tags->getFirst();
		} else {
			ereg('([A-z0-9]+).([A-z0-9]+)=(.+)', $shortname, $tag_parts);

			switch ($tag_parts[1]) {
			case 'date' :
				$tag = new PinholeDateTag($this->db, $tag_parts[2], $tag_parts[3]);
				break;
			case 'exif' :
				// TODO: no working example yet
				$tag = new PinholeExifTag($this->db, $tag_parts[2], $tag_parts[3]);
				break;
			}

			if (!$tag->isValid())
				$tag = null;
		}

		if ($tag !== null)
			$this->addTag($tag);
	}

	// }}}
	// {{{ public function getIntersectingTags()

	public function getIntersectingTags($class_name = null)
	{
		if ($class_name === null)
			return $this->tags;
	
		$tags = array();

		foreach ($this->tags as $tag)
			if ($tag instanceof $class_name)
				$tags[] = $tag;

		return $tags;
	}

	// }}}
	// {{{ public function getIntersectingTagPath()

	public function getIntersectingTagPath()
	{
		$path = '';
		$count = 0;
		foreach ($this->tags as $tag) {
			if ($count > 0)
				$path.= '/';

			$path.= $tag->getPath();
			$count++;
		}

		return $path;
	}

	// }}}
	// {{{ public function getPhotos()

	public function getPhotos($limit = null, $offset = 0)
	{
		$photos = PinholePhotoWrapper::loadSetFromDBWithDimension(
			$this->db, 'thumb', $this->getTagWhereClause(),
			'', $limit, $offset);

		return $photos;
	}

	// }}}
	// {{{ public function getPhotoCount()

	public function getPhotoCount()
	{
		$sql = sprintf('select count(id) from PinholePhoto where %s',
			$this->getTagWhereClause());

		return SwatDB::queryOne($this->db, $sql);
	}

	// }}}
	// {{{ public function getPhotoCountByDate()

	public function getPhotoCountByDate()
	{
		return SwatDB::getOptionArray($this->db, 'PinholePhotoCountByDateView',
			'photo_count', 'photo_date', null,
			$this->getTagWhereClause('PinholePhotoCountByDateView'));
	}

	// }}}
	// {{{ public function getTags()

	public function getTags()
	{
		$sql = 'select * from PinholeTag where id not in (%s) and id in (
			select tag from PinholePhotoTagBinding where photo in (
				select id from PinholePhoto where 1 = 1';

		$tag_ids = array();
		foreach ($this->tags as $tag) {
			$sql.= ' and '.$tag->getWhereClause();

			if ($tag instanceof PinholeTag)
				$tag_ids[] = $tag->id;
		}

		$sql.= '))';


		if (count($tag_ids))
			$sql = sprintf($sql,
				$this->db->implodeArray($tag_ids, 'integer'));
		else
			// hack to experiment with showing all tags on top level
			$sql = sprintf($sql, '0');

		// TODO: use classmap
		$tags = SwatDB::query($this->db, $sql, 'PinholeTagWrapper');

		return $tags;
	}

	// }}}
	// {{{ protected function getTagWhereClause()

	protected function getTagWhereClause($table_name = 'PinholePhoto')
	{
		$where_clause = sprintf('%s.status = %s',
			$table_name,
			$this->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer'));

		foreach ($this->tags as $tag)
			$where_clause.= ' and '.$tag->getWhereClause($table_name);

		return $where_clause;
	}

	// }}}
}

?>
