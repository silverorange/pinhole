<?php

require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatDB/SwatDBRange.php';
require_once 'SwatDB/SwatDBRecordable.php';
require_once 'Swat/exceptions/SwatInvalidClassException.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Pinhole/PinholeTagFactory.php';

/**
 * A list of tag objects
 *
 * Tag lists are the main way to interact with tags in Pinhole. Tag lists can
 * be used to easily select a set of photos and to quickly parse multiple
 * tag strings into a collection of tag objects.
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeTagList implements Iterator, Countable, SwatDBRecordable
{
	/**
	 * The tags of this list indexed by tag string
	 *
	 * @var array
	 */
	private $tags = array();

	/**
	 * The tag strings of the tags of this list indexed numerically
	 *
	 * This array is maintained for the iterator interface.
	 *
	 * @var array
	 */
	private $tag_keys = array();

	/**
	 * The current iterator index of this list
	 *
	 * @var integer
	 */
	private $tag_index = 0;

	/**
	 * The database connection used by this tag list
	 *
	 * @var MDB2_Driver_Common
	 *
	 * @see PinholeTagList::setDatabase()
	 */
	private $db;

	/**
	 * Creates a new tag list
	 *
	 * @param string $tag_list_string a list of tag strings separated by '/'
	 *                                 characters that are added to the list
	 *                                 when the list is created. Duplicate
	 *                                 tag strings are ignored.
	 *
	 * @todo Set the database connection in constructor somehow, maybe through
	 *       the tag factory.
	 * @todo Use a more efficient algorithm for loading multiple tags from the
	 *       database.
	 */
	public function __construct($string = null)
	{
		if (is_string($tag_list_string)) {
			$tag_strings = explode('/', $tag_list_string);
			$tag_strings = array_unique($tag_strings);
			foreach ($tag_strings as $tag_string) {
				$tag = PinholeTagFactory::get($tag_string);
				if ($tag) {
					$this->add($tag);
				}
			}
		}
	}

	/**
	 * Gets a string representation of this tag list
	 *
	 * @return string a string representation of this tag list. The string is
	 *                 an ordered list of string representations of the
	 *                 individual tags delimited by '/' characters. There are
	 *                 no leading or trailing '/' characters in the returned
	 *                 string.
	 */
	public function __toString()
	{
		$string = '';
		foreach ($this as $tag)
			$string.= '/'.$tag->__toString();

		// strip leading slash
		return substr($string, 1);
	}

	/**
	 * Gets the join clauses used by the tags in this tag list
	 *
	 * @return array the join clauses used by the tags in this tag list.
	 */
	public function getJoinClauses()
	{
		$join_clauses = array();
		foreach ($this as $tag)
			$join_clauses = array_merge($tag->getJoinClauses(), $join_clauses);

		return $join_clauses;
	}

	/**
	 * Gets the where clause used by the tags in this tag list
	 *
	 * @return array the where clause used by the tags in this tag list.
	 *                Individual where clauses of the tags in this list are
	 *                ANDed together to form the final clause.
	 *
	 * @todo Make it possible to OR clauses as well as ANDing them. Remember
	 *       to not OR 1 = 1 clauses.
	 */
	public function getWhereClause()
	{
		$where_clause = '1 = 1';
		foreach ($this as $tag)
			$where_clause.= ' and '.$tag->getWhereClause();

		return $where_clause;
	}

	/**
	 * Gets the database range of the tags in this tag list
	 *
	 * The range of this list is defined as the maximum limit and the
	 * maximum offset of all contained tags.
	 *
	 * @return SwatDBRange the range of the tags in this tag list or null if
	 *                      the tags in this list do not define a database
	 *                      range.
	 */
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

	/**
	 * Gets a tag in this tag list
	 *
	 * @param string|PinholeAbstractTag $tag either a tag string or a tag
	 *                                        object representing the tag to
	 *                                        get.
	 *
	 * @return PinholeAbstractTag the tag object contained in this list if it
	 *                             is contained in this list and null if the
	 *                             requested tag does not exist in this list.
	 *
	 * @throws SwatInvalidClassException if the <i>$tag</i> parameter is
	 *                                    neither a string nor a
	 *                                    PinholeAbstractTag.
	 */
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

	/**
	 * Gets whether or not this list contains a particular tag
	 *
	 * @param string|PinholeAbstractTag $tag either a tag string or a tag
	 *                                        object representing the tag to
	 *                                        check for.
	 *
	 * @return boolean true if this list contains the given tag and false if
	 *                  this list does not contain the given tag.
	 *
	 * @throws SwatInvalidClassException if the <i>$tag</i> parameter is
	 *                                    neither a string nor a
	 *                                    PinholeAbstractTag.
	 */
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

	/**
	 * Adds a tag to this tag list
	 *
	 * If this list has a database set through the
	 * {@link PinholeTagList::setDatabase()} method and the tag to be
	 * added is not already contained in this list, the database will be
	 * set on the added tag as well.
	 *
	 * @param string|PinholeAbstractTag $tag either a tag string or a tag
	 *                                        object to add to this list.
	 *
	 * @return PinholeAbstractTag the added tag object, or the existing tag
	 *                             object if this list already contains the
	 *                             given tag. If the added tag is specified as
	 *                             a tag string and the tag string could not be
	 *                             parsed, null is returned.
	 *
	 * @throws SwatInvalidClassException if the <i>$tag</i> parameter is
	 *                                    neither a string nor a
	 *                                    PinholeAbstractTag.
	 */
	public function add($tag)
	{
		$added_tag = null;

		if (is_string($tag)) {
			$tag = PinholeTagFactory::get($tag);
		} elseif (!($tag instanceof PinholeAbstractTag)) {
			throw new SwatInvalidClassException(
				'$tag must be either a string or a PinholeAbstractTag',
				0, $tag);
		}

		if ($tag !== null) {
			if ($this->contains($tag)) {
				$added_tag = $this->get($tag);
			} else {
				if ($this->db instanceof MDB2_Driver_Common)
					$tag->setDatabase($this->db);

				$this->tag_keys[] = $tag->__toString();
				$this->tags[$tag->__toString()] = $tag;
				$added_tag = $tag;
			}
		}

		return $added_tag;
	}

	/**
	 * Removes a tag from this tag list
	 *
	 * @param string|PinholeAbstractTag $tag either a tag string or a tag
	 *                                        object representing the tag to
	 *                                        remove.
	 *
	 * @return PinholeAbstractTag the removed tag object or null if this list
	 *                             does not contain the given tag.
	 *
	 * @throws SwatInvalidClassException if the <i>$tag</i> parameter is
	 *                                    neither a string nor a
	 *                                    PinholeAbstractTag.
	 */
	public function remove($tag)
	{
		$removed = null;

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
			array_splice($this->tag_keys, $index, 1);
			if ($this->tag_index >= $index && $this->tag_index > 0)
				$this->tag_index--;
		}

		return $removed;
	}

	/**
	 * Gets the tags in this tag list that are of a specified type
	 *
	 * @param string $type the class name of the type of tags to get.
	 *
	 * @return PinholeTagList a list of tags of the specified type. If this
	 *                         list does not contain any tags of the specified
	 *                         type, an empty tag list is returned.
	 *
	 * @throws SwatInvalidClassException if the specified <i>$type</i> is not a
	 *                                    subclass of PinholeAbstractTag.
	 */
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

	/**
	 * Gets the photos of this tag list
	 *
	 * Photos are defined as an intersection of all the photos of all the tags
	 * in this list.
	 *
	 * @return PinholePhotoWrapper the photos of this tag list.
	 */
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

	/**
	 * Gets the number of photos of this tag list
	 *
	 * The photo count is the number of photos in the intersection of all the
	 * photos of all the tags in this list.
	 *
	 * @return integer the number of photos in this tag list.
	 */
	public function getPhotoCount()
	{
		$sql = sprintf('select count(id) from PinholePhoto %s where %s',
			implode("\n", $this->getJoinClauses()),
			$this->getWhereClause());

		return SwatDB::queryOne($this->db, $sql);
	}

	/**
	 * Gets a list of tags not in this list that also apply to the photos
	 * of this list
	 *
	 * @return PinholeTagList a list of tags not in this list that also apply
	 *                         to the photos of this list.
	 *
	 * @see PinholeTagList::getPhotos()
	 *
	 * @todo implement this method.
	 */
	public function getSubTags()
	{
	}

	/**
	 * Sets the database connection used by this tag list
	 *
	 * @param MDB2_Driver_Common $db the database connection to use for this
	 *                                tag list.
	 */
	public function setDatabase(MDB2_Driver_Common $db)
	{
		$this->db = $db;
		foreach ($this as $tag)
			$tag->setDatabase($db);
	}

	/**
	 * Saves this tag list
	 *
	 * This saves all tags contained in this tag list.
	 */
	public function save()
	{
		foreach ($this as $tag)
			$tag->save();
	}

	/**
	 * Loads this tag list
	 *
	 * @param string $string the string to load this tag list from.
	 *
	 * @return boolean true if this tag list was loaded successfully and false
	 *                  if this tag list could not be loaded with the given
	 *                  string.
	 *
	 * @todo what does loading a tag list mean?
	 */
	public function load($string)
	{
	}

	/**
	 * Deletes this tag list
	 *
	 * This deletes all the tags contained in this tag list. After deleting,
	 * the list still contains the deleted tags as PHP objects; however, the
	 * deleted tags will no longer exist in the database.
	 *
	 * @see PinholeTagList::remove()
	 */
	public function delete()
	{
		foreach ($this as $tag)
			$tag->delete();
	}

	/**
	 * Whether or not this tag list is modified
	 *
	 * This tag list is modified if any of the contained tags are modified.
	 *
	 * @return boolean true if this tag list is modified and false if it is
	 *                  not.
	 */
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

	/**
	 * Gets the current tag pointed to by this list's iterator interface
	 *
	 * @return PinholeAbstractTag the current tag pointed to by this list's
	 *                             iterator interface.
	 */
	public function current()
	{
		return $this->tags[$this->tag_keys[$this->tag_index]];
	}

	/**
	 * Gets whether or not the current location pointed to by this list's
	 * iterator interface is a valid location
	 *
	 * @return boolean true if the location is valid and false if it is not.
	 */
	public function valid()
	{
		return array_key_exists($this->tag_index, $this->tag_keys);
	}

	/**
	 * Advances the iterator interface to the next tag in this list
	 */
	public function next()
	{
		$this->tag_index++;
	}

	/**
	 * Retreats the iterator interface to the previous tag in this list
	 */
	public function prev()
	{
		$this->tag_index--;
	}

	/**
	 * Rewinds the iterator interface to the first tag in this list
	 */
	public function rewind()
	{
		$this->tag_index = 0;
	}

	/**
	 * Gets the key (tag string) of the current tag pointed to by this list's
	 * iterator interface
	 *
	 * @return string the key (tag string) of the current tag pointed to by
	 *                 this list's iterator interface.
	 */
	public function key()
	{
		return $this->tag_keys[$this->tag_index];
	}

	/**
	 * Gets the number of tags in this tag list
	 *
	 * This satisfies the Countable interface.
	 *
	 * @return integer the number of tags in this tag list.
	 */
	public function count()
	{
		return count($this->tags);
	}
}

?>
