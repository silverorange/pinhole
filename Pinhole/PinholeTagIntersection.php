<?php

require_once 'Pinhole/dataobjects/PinholeTagWrapper.php';

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
}

?>
