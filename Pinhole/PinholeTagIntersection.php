<?php

require_once 'Pinhole/dataobjects/PinholeTagWrapper.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';

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
		$sql = sprintf('select * from PinholeTag where shortname = %s',
			$this->db->quote($shortname, 'text'));

		// TODO: use classmap
		$tags = SwatDB::query($this->db, $sql, 'PinholeTagWrapper');
		$tag = $tags->getFirst();

		if ($tag !== null)
			$this->addTag($tag);
	}

	// }}}
	// {{{ public function getIntersectingTags()

	public function getIntersectingTags()
	{
		return $this->tags;
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

			$path.= $tag->shortname;
			$count++;
		}

		return $path;
	}

	// }}}
	// {{{ public function getPhotos()

	public function getPhotos($limit = null, $offset = null)
	{
		$where_clause = '1 = 1';

		foreach ($this->tags as $tag)
			$where_clause.= sprintf(' and PinholePhoto.id in
				(select PinholePhotoTagBinding.photo
				from PinholePhotoTagBinding
				where PinholePhotoTagBinding.tag = %s)',
				$this->db->quote($tag->id, 'integer'));

		$photos = PinholePhotoWrapper::loadSetFromDBWithDimension(
			$this->db, 'thumb', $where_clause, 20, $offset);

		return $photos;
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
			$sql.= sprintf(' and id in (select photo from '.
				'PinholePhotoTagBinding where tag = %s)',
				$this->db->quote($tag->id, 'integer'));

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
}

?>
