<?php

require_once 'Pinhole/dataobjects/PinholeTagWrapper.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Pinhole/PinholeDateTag.php';
require_once 'Pinhole/PinholeSearchTag.php';
require_once 'Pinhole/PinholeSiteTag.php';

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
			case 'search' :
				$tag = new PinholeSearchTag($this->db, $tag_parts[2], $tag_parts[3]);
				break;
			case 'site' :
				$tag = new PinholeSiteTag($this->db, $tag_parts[2], $tag_parts[3]);
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
			if ($class_name === null ||
				$tag instanceof $class_name)
				$tags[] = $tag;

		return $tags;
	}

	// }}}
	// {{{ public function getIntersectingTagPath()

	public function getIntersectingTagPath($class_name = null,
		$remove_machine_tags = array())
	{
		$path = '';
		$count = 0;
		foreach ($this->getIntersectingTags($class_name) as $tag) {
			$tag_path = $tag->getPath();

			if ($tag instanceof PinholeMachineTag) {
				$p = substr($tag_path, 0, strpos($tag_path, '='));
				if (in_array($p, $remove_machine_tags))
					continue;
			}

			if ($tag_path !== null) {
				if ($count > 0)
					$path.= '/';

				$path.= $tag_path;
				$count++;
			}
		}

		return ($path == '') ? null : $path;
	}

	// }}}
	// {{{ public function getPhotos()

	public function getPhotos($page_size)
	{
		$photos = PinholePhotoWrapper::loadSetFromDBWithDimension(
			$this->db, 'thumb', $this->getTagWhereClause(),
			$this->getTagJoinClause(), $page_size,
			$page_size * $this->getCurrentPage());

		return $photos;
	}

	// }}}
	// {{{ public function getPhotoCount()

	public function getPhotoCount()
	{
		$sql = sprintf('select count(id) from PinholePhoto %s where %s',
			$this->getTagJoinClause(),
			$this->getTagWhereClause());

		return SwatDB::queryOne($this->db, $sql);
	}

	// }}}
	// {{{ public function getPhotoCountByDate()

	public function getPhotoCountByDate($date_part = 'day')
	{
		$group_by_parts = array();

		switch ($date_part) {
			case 'day' :
				$group_by_parts[] = 'day';
				$group_by_parts[] = 'month';
				$group_by_parts[] = 'year';
				$date_format = '%Y-%m-%d';
				break;
			case 'month' :
				$group_by_parts[] = 'month';
				$group_by_parts[] = 'year';
				$date_format = '%Y-%m';
				break;
			case 'year' :
				$group_by_parts[] = 'year';
				$date_format = '%Y';
				break;
		}

		$group_by = '';

		$count = 0;
		foreach ($group_by_parts as $part) {
			if ($count > 0)
				$group_by.= ', ';

			$group_by.= sprintf('date_part(%s, PinholePhoto.photo_date)',
				$this->db->quote($part, 'text'));

			$count++;
		}

		$sql = sprintf('select count(PinholePhoto.id) as photo_count,
				max(PinholePhoto.photo_date) as photo_date
			from PinholePhoto
			%s
			where %s
			group by %s',
			$this->getTagJoinClause(),
			$this->getTagWhereClause(),
			$group_by);

		$rows = SwatDB::query($this->db, $sql);

		$dates = array();

		foreach ($rows as $row) {
			$date = new SwatDate($row->photo_date);
			$dates[$date->format($date_format)] = $row->photo_count;
		}

		return $dates;
	}

	// }}}
	// {{{ public function getDateRange()

	public function getDateRange()
	{
		$sql = sprintf('select max(photo_date) as last_photo_date,
				min(photo_date) as first_photo_date
			from PinholePhoto
			%s
			where %s',
			$this->getTagJoinClause(),
			$this->getTagWhereClause());

		$date_range = SwatDB::queryRow($this->db, $sql);

		if ($date_range === null)
			return null;
		else
			return array(new SwatDate($date_range->first_photo_date),
				new SwatDate($date_range->last_photo_date));
	}

	// }}}
	// {{{ public function getTags()

	public function getTags()
	{
		$sql = 'select PinholeTag.id, PinholeTag.title, PinholeTag.shortname,
				max(publish_date) as last_updated,
				count(PinholePhoto.id) as photo_count
			from PinholeTag 
			inner join PinholePhotoTagBinding on PinholeTag.id = PinholePhotoTagBinding.tag
			inner join PinholePhoto on PinholePhoto.id = PinholePhotoTagBinding.photo
			%2$s
			where PinholeTag.id not in (%1$s) and %3$s
			group by PinholeTag.id, PinholeTag.title, PinholeTag.shortname
			order by max(publish_date) desc';

		$tag_ids = array();
		foreach ($this->getIntersectingTags('PinholeTag') as $tag)
			$tag_ids[] = $tag->id;

		// this '0' is a hack to get all tags to show up on the base
		// level
		$sql = sprintf($sql,
			(count($tag_ids) > 0) ?
				$this->db->implodeArray($tag_ids, 'integer') : '0',
			$this->getTagJoinClause(),
			$this->getTagWhereClause());

		//$this->db->setLimit(30);

		$rows = SwatDB::query($this->db, $sql);
		$tag_wrapper = new PinholeTagWrapper();

		foreach ($rows as $row) {
			$tag = new PinholeTag($row);
			$tag->setLastUpdated(new SwatDate($row->last_updated));
			$tag->setPhotoCount($row->photo_count);
			$tag_wrapper->add($tag);
		}

		return $tag_wrapper;
	}

	// }}}
	// {{{ public function getCurrentPage()

	public function getCurrentPage()
	{
		foreach ($this->getIntersectingTags('PinholeSiteTag') as $tag) {
			$page = $tag->getPage();
			if ($page !== null)
				return $page;
		}

		return 0;
	}

	// }}}
	// {{{ protected function getTagWhereClause()

	protected function getTagWhereClause()
	{
		$where_clause = sprintf('PinholePhoto.status = %s',
			$this->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer'));

		foreach ($this->getIntersectingTags() as $tag) {
			$tag_where_clause = $tag->getWhereClause();
			if ($tag_where_clause !== null)
				$where_clause.= ' and '.$tag_where_clause;
		}

		return $where_clause;
	}

	// }}}
	// {{{ protected function getTagJoinClause()

	protected function getTagJoinClause()
	{
		$join_clause = '';

		foreach ($this->getIntersectingTags() as $tag) {
			$tag_join_clause = $tag->getJoinClause();
			if ($tag_join_clause !== null)	
				$join_clause.= ' '.$tag->getJoinClause();
		}

		return $join_clause;
	}

	// }}}
}

?>
