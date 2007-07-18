<?php

require_once 'MDB2.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatDB/SwatDBRange.php';
require_once 'SwatDB/SwatDBRecordable.php';
require_once 'Swat/exceptions/SwatInvalidClassException.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Pinhole/dataobjects/PinholeTagDataObjectWrapper.php';
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
	// {{{ class constants

	/**
	 * Operator for selecting the set of photos in the intersection of tags in
	 * this list.
	 */
	const OPERATOR_AND = 'and';

	/**
	 * Operator for selecting the set of photos in the union of tags in this
	 * list.
	 */
	const OPERATOR_OR  = 'or';

	/**
	 * Operator for excluding the set of photos in this tag list.
	 */
	const OPERATOR_NOT = 'not';

	// }}}
	// {{{ private properties

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

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new tag list
	 *
	 * @param MDB2_Driver_Common $db The database connection to use for this
	 *                                tag list.
	 * @param string $tag_list_string optional. A list of tag strings separated
	 *                                 by '/' characters that are added to this
	 *                                 list when the list is created. Duplicate
	 *                                 tag strings are ignored.
	 */
	public function __construct(MDB2_Driver_Common $db, $tag_list_string = null)
	{
		$this->setDatabase($db);

		if (is_string($tag_list_string)) {
			$tag_strings = explode('/', $tag_list_string);
			$tag_strings = array_unique($tag_strings);

			// get all simple tag strings
			$simple_tag_strings = preg_grep('/^[A-Za-z0-9]+$/', $tag_strings);
			$quoted_tag_strings =
				$db->implodeArray($simple_tag_strings, 'text');

			// load all simple tags in one query
			$sql = sprintf('select * from PinholeTag where shortname in (%s)',
				$quoted_tag_strings);

			$tag_data_objects =
				SwatDB::query($db, $sql, 'PinholeTagDataObjectWrapper');

			foreach ($tag_strings as $tag_string) {
				// check if we've already loaded a simple tag for this string
				$data_object = $tag_data_objects->getByIndex($tag_string);
				if ($data_object === null) {
					$tag = PinholeTagFactory::get($tag_string, $db);
					if ($tag !== false) {
						$this->add($tag);
					}
				} else {
					$tag = new PinholeTag($data_object);
					$this->add($tag);
				}
			}
		}
	}

	// }}}
	// {{{ public function __toString()

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

	// }}}
	// {{{ public function getJoinClauses()

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

		// normalize
		foreach ($join_clauses as &$join_clause) {
			$join_clause = strtolower($joinclause);
			$join_clause = preg_replace('/\s+/', '', $joinclause);
		}

		// remove duplicates
		$join_clauses = array_unique($join_clauses);

		return $join_clauses;
	}

	// }}}

	/**
	 * Gets the where clause used by the tags in this tag list
	 *
	 * @return array the where clause used by the tags in this tag list.
	 *                Individual where clauses of the tags in this list are
	 *                ANDed together to form the final clause.
	 *
	 * @todo Make it possible to OR clauses as well as ANDing them.
	 */
	public function getWhereClause()
	{
		$operator = self::OPERATOR_AND;

		$where_clauses = array();
		foreach ($this as $tag)
			$where_clauses[] = '('.$tag->getWhereClause().')';

		if ($operator == self::OPERATOR_OR)
			$where_clauses = array_diff($where_clauses, array('1 = 1'));

		$where_clause = implode(' '.$operator.' ', $where_clauses);

		return $where_clause;
	}

	// {{{ public function getRange()

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

	// }}}
	// {{{ public function get()

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

	// }}}
	// {{{ public function contains()

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

	// }}}
	// {{{ public function add()

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
			$tag = PinholeTagFactory::get($tag, $this->db);
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

	// }}}
	// {{{ public function remove()

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

	// }}}
	// {{{ public function replace()

	/**
	 * Replaces a tag in this tag list
	 *
	 * @param string|PinholeAbstractTag $tag either a tag string or a tag
	 *                                        object representing the tag to
	 *                                        replace.
	 * @param PinholeAbstractTag $replacement the replacement tag.
	 *
	 * @return PinholeAbstractTag the replacedtag object or null if this list
	 *                             does not contain the given tag.
	 *
	 * @throws SwatInvalidClassException if the <i>$tag</i> parameter is
	 *                                    neither a string nor a
	 *                                    PinholeAbstractTag.
	 */
	public function replace($tag, PinholeAbstractTag $replacement)
	{
		$replaced = null;
		if ($tag instanceof PinholeAbstractTag)
			$tag = $tag->__toString();

		if (!is_string($tag)) {
			throw new SwatInvalidClassException(
				'$tag must be either a string or a PinholeAbstractTag',
				0, $tag);
		}

		if ($this->contains($replacement)) {
			$replaced = $this->remove($tag);
		} else {
			if ($this->contains($tag)) {
				$replaced = $this->get($tag);
				unset($this->tags[$tag]);
				$index = array_search($tag, $this->tag_keys);
				array_splice($this->tag_keys, $index, 1,
					array($replacement->__toString()));

				$this->tags[$replacement->__toString()] = $replacement;
			}
		}

		return $replaced;
	}

	// }}}
	// {{{ public function filter()

	/**
	 * Gets a new tag list based on this tag list with certain tag types
	 * filtered out
	 *
	 * @param array $filter_types an array of class names of tag types to filter
	 *                       out of the returned list.
	 * @param boolean $filter_subclasses optional. Whether or not to filter
	 *                                    tags that are subclasses of the filter
	 *                                    tag types. By default, subclasses
	 *                                    are filtered.
	 *
	 * @return PinholeTagList a new tag list containing all the tags of this
	 *                         list except for the specified tag types.
	 */
	public function filter(array $filter_types, $filter_subclasses = true)
	{
		$tag_list = new PinholeTagList($this->db);

		foreach ($this as $tag) {
			if ($filter_subclasses) {
				$filtered = false;
				foreach ($filter_types as $type) {
					if ($tag instanceof $type) {
						$filtered = true;
						break;
					}
				}
			} else {
				$filtered = in_array(get_class($tag), $filter_types);
			}

			if (!$filtered) {
				$tag_list->add($tag);
			}
		}

		return $tag_list;
	}

	// }}}
	// {{{ public function union()

	/**
	 * Gets a new tag list that is the union of this tag list with another tag
	 * list
	 *
	 * @param PinholeTagList $tag_list the tag list to union with this tag list.
	 *
	 * @return PinholeTagList a new tag list that is the union of this tag
	 *                         list with the tag list specified in
	 *                         <i>$tag_list</i>.
	 */
	public function union(PinholeTagList $tag_list)
	{
		$new_tag_list = clone $this;

		foreach ($tag_list as $tag)
			$new_tag_list->add($tag);

		return $new_tag_list;
	}

	// }}}
	// {{{ public function intersect()

	/**
	 * Gets a new tag list that is the intersection of this tag list with
	 * another tag list
	 *
	 * @param PinholeTagList $tag_list the tag list to intersect with this tag
	 *                                  list.
	 *
	 * @return PinholeTagList a new tag list that is the intersection of this
	 *                         tag list with the tag list specified in
	 *                         <i>$tag_list</i>.
	 */
	public function intersect(PinholeTagList $tag_list)
	{
		$new_tag_list = new PinholeTagList($this->db);

		foreach ($this as $tag)
			if ($tag_list->contains($tag))
				$new_tag_list->add($tag);

		return $new_tag_list;
	}

	// }}}
	// {{{ public function subtract()

	/**
	 * Gets a new tag list that is the complement of this tag list with
	 * another tag list
	 *
	 * @param PinholeTagList $tag_list the tag list to subtract from this tag
	 *                                  list.
	 *
	 * @return PinholeTagList a new tag list that is the complement of this
	 *                         tag list with the tag list specified in
	 *                         <i>$tag_list</i>.
	 */
	public function subtract(PinholeTagList $tag_list)
	{
		$new_tag_list = clone $this;

		foreach ($tag_list as $tag)
			$new_tag_list->remove($tag);

		return $new_tag_list;
	}

	// }}}
	// {{{ public function getByType()

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

	// }}}
	// {{{ public function getPhotos()

	/**
	 * Gets the photos of this tag list
	 *
	 * Photos are defined as an intersection of all the photos of all the tags
	 * in this list.
	 *
	 * @return PinholePhotoWrapper the photos of this tag list.
	 *
	 * @todo add ability to set order of returned photo set.
	 */
	public function getPhotos()
	{
		$sql = sprintf('select * from PinholePhoto %s where %s',
			implode("\n", $this->getJoinClauses()),
			$this->getWhereClause());

//		if ($order_by_clause !== null)
//			$sql = $sql.' '.$order_by_clause;

		$range = $this->getRange();
		if ($range !== null)
			$this->db->setRange($range->getLimit(), $range->getOffset());

		$wrapper = SwatDBClassMap::get('PinholePhotoWrapper');
		return SwatDB::query($this->db, $sql, $wrapper);
	}

	// }}}
	// {{{ public function getPhotoCount()

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

	// }}}

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

	// {{{ public function setDatabase()

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

	// }}}
	// {{{ public function save()

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

	// }}}

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

	// {{{ public function delete()

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

	// }}}
	// {{{ public function isModified()

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

	// }}}
	// {{{ public function current()

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

	// }}}
	// {{{ public function valid()

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

	// }}}
	// {{{ public function next()

	/**
	 * Advances the iterator interface to the next tag in this list
	 */
	public function next()
	{
		$this->tag_index++;
	}

	// }}}
	// {{{ public function rewind()

	/**
	 * Rewinds the iterator interface to the first tag in this list
	 */
	public function rewind()
	{
		$this->tag_index = 0;
	}

	// }}}
	// {{{ public function key()

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

	// }}}
	// {{{ public function count()

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

	// }}}
}

?>
