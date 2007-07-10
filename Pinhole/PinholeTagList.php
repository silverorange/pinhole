<?php

require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatDB/SwatDBRange.php';
require_once 'SwatDB/SwatDBRecordable.php';
require_once 'Swat/exceptions/SwatInvalidClassException.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Pinhole/PinholeTagFactory.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeTagList implements Iterator, Countable, SwatDBRecordable
{
	private $tags = array();
	private $tag_keys = array();
	private $tag_index = 0;

	private $db;

	// TODO: set database connection in constructor somehow, maybe through the
	//       tag factory.
	public function __construct($string = null)
	{
		if (is_string($string)) {
			$tag_strings = explode('/', $string);
			$tag_strings = array_unique($tag_strings);
			// TODO: algorithm on my tomboy note for efficient tag loading
			foreach ($tag_strings as $tag_string) {
				$tag = PinholeTagFactory::get($tag_string);
				if ($tag) {
					$this->add($tag);
				}
			}
		}
	}

	public function __toString()
	{
		$string = '';
		foreach ($this as $tag)
			$string.= '/'.$tag->__toString();

		// strip leading slash
		$string = substr($tag, 1);
		return $string;
	}

	public function getJoinClauses()
	{
		$join_clauses = array();
		foreach ($this as $tag)
			$join_clauses = array_merge($tag->getJoinClauses(), $join_clauses);

		return $join_clauses;
	}

	public function getWhereClause()
	{
		// TODO: make it possible to or clauses instead of and them.
		//       remember not to or 1 = 1 clauses
		$where_clause = '1 = 1';
		foreach ($this as $tag)
			$where_clause.= ' and '.$tag->getWhereClause();

		return $where_clause;
	}

	public function getRange()
	{
		$range = null;
		$limit = 0;
		$offset = 0;
		foreach($this as $tag) {
			$tag_range = $tag->getRange();
			if ($tag_range !== null) {
				$limit = max($limit, $tag_range->getLimit());
				$offset = max($offset, $tag_range->getOffset());
			}
		}

		if ($limit > 0 || $offset > 0)
			$range = new SwatDBRange($limit, $offset);

		return $range;
	}

	public function get($tag)
	{
		if ($tag instanceof PinholeAbstractTag)
			$tag = $tag->__toString();

		if (!is_string($tag)) {
			throw new SwatInvalidClassException(
				'$tag must be either a string or a PinholeAbstractTag',
				0, $tag);
		}

		if (array_key_exists($tag, $this->tags))
			$tag = $this->tags[$tag];
		else
			$tag = null;

		return $tag;
	}

	public function contains($tag)
	{
		if ($tag instanceof PinholeAbstractTag)
			$tag = $tag->__toString();

		if (!is_string($tag)) {
			throw new SwatInvalidClassException(
				'$tag must be either a string or a PinholeAbstractTag',
				0, $tag);
		}

		return array_key_exists($tag, $this->tags);
	}

	public function add($tag)
	{
		if (is_string($tag)) {
			$tag = PinholeTagFactory::get($tag);
			if ($tag === false) {
			}
		}

		if (!($tag instanceof PinholeAbstractTag)) {
			throw new SwatInvalidClassException(
				'$tag must be either a string or a PinholeAbstractTag',
				0, $tag);
		}

		if ($this->contains($tag)) {
			$tag = $this->get($tag);
		} else {
			if ($this->db instanceof MDB2_Driver_Common)
				$tag->setDatabase($this->db);

			$this->tag_keys[] = $tag->__toString();
			$this->tags[$tag->__toString()] = $tag;
		}

		return $tag;
	}

	public function remove($tag)
	{
		$removed = false;

		if ($tag instanceof PinholeAbstractTag)
			$tag = $tag->__toString();

		if (!is_string($tag)) {
			throw new SwatInvalidClassException(
				'$tag must be either a string or a PinholeAbstractTag',
				0, $tag);
		}

		if ($this->contains($tag)) {
			$removed = $this->get($tag);
			unset($this->tags[$tag]);
			$index = array_search($tag, $this->tag_keys);
			unset($this->tag_keys[$index]);
			if ($this->tag_index >= $index && $this->tag_index > 0)
				$this->tag_index--;
		}

		return $removed;
	}

	public function getByType($type)
	{
		if (!is_subclass_of($type, 'PinholeAbstractTag'))
			throw new SwatInvalidClassException(
				'$type must be a subclass of PinholeAbstractTag');

		$tag_list = new PinholeTagList();
		if ($this->db instanceof MDB2_Driver_Common)
			$tag_list->setDatabase($this->db);

		foreach ($this as $tag)
			if ($tag instanceof $type)
				$tag_list->add($tag);

		return $tag_list;
	}

	public function getPhotos()
	{
		$sql = sprintf('select * from PinholePhoto %s where %s',
			implode("\n", $this->getJoinClauses()),
			$this->getWhereClause());

		$range = $this->getRange();
		if ($range !== null)
			$this->db->setRange($range->getLimit(), $range->getOffset());

		$wrapper = SwatDBClassMap::get('PinholePhotoWrapper');
		return SwatDB::query($this->db, $sql, $wrapper);
	}

	public function getPhotoCount()
	{
		$sql = sprintf('select count(id) from PinholePhoto %s where %s',
			implode("\n", $this->getJoinClauses()),
			$this->getWhereClause());

		return SwatDB::queryOne($this->db, $sql);
	}

	public function getSubTags()
	{
		// TODO: implement me
	}

	public function setDatabase(MDB2_Driver_Common $db)
	{
		$this->db = $db;
		foreach ($this as $tag)
			$tag->setDatabase($db);
	}

	public function save()
	{
		foreach ($this as $tag)
			$tag->save();
	}

	public function load($id)
	{
		// TODO: what to do here?
	}

	public function delete()
	{
		foreach ($this as $tag)
			$tag->delete();
	}

	public function isModified()
	{
		$modified = false;
		foreach ($this as $tag) {
			if ($tag->isModified()) {
				$modified = true;
				break;
			}
		}
		return $modified;
	}

	public function current()
	{
		return $this->tags[$this->tag_keys[$this->tag_index]];
	}

	public function valid()
	{
		return array_key_exists($this->tag_index, $this->tag_keys);
	}

	public function next()
	{
		$this->tag_index++;
	}

	public function prev()
	{
		$this->tag_index--;
	}

	public function rewind()
	{
		$this->tag_index = 0;
	}

	public function key()
	{
		return $this->tag_keys[$this->tag_index];
	}

	public function count()
	{
		return count($this->tags);
	}
}

?>
