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
	// {{{ protected function __construct()

	public function __construct($db)
	{
		$this->db = $db;
	}

	// }}}
	// {{{ protected function addTag()

	public function addTag($tag)
	{
		$this->tags[] = $tag;
	}

	// }}}
	// {{{ protected function addTagById()

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
	// {{{ protected function addTagByShortname()

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
	// {{{ protected function getIntersectingTags()

	public function getIntersectingTags()
	{
		return $this->tags;
	}

	// }}}
	// {{{ protected function getPhotos()

	public function getPhotos()
	{
		$sql ='select * from PinholePhoto where 1 = 1';

		foreach ($this->tags as $tag) {
			$sql.= sprintf(' and id in (select photo from '.
				'PinholePhotoTagBinding where tag = %s)',
				$this->db->quote($tag->id, 'integer'));
		}

		$sql.= ' order by publish_date desc';

		// TODO: use classmap
		$photos = SwatDB::query($this->db, $sql, 'PinholePhotoWrapper');

		return $photos;
	}

	// }}}
}

?>
