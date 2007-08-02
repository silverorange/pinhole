<?php

require_once 'MDB2.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatDB/SwatDBRange.php';
require_once 'SwatDB/SwatDBRecordable.php';
require_once 'Swat/exceptions/SwatInvalidClassException.php';
require_once 'Pinhole/PinholeTagFactory.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Pinhole/dataobjects/PinholeTagDataObjectWrapper.php';
require_once 'Pinhole/dataobjects/PinholeInstance.php';
require_once 'Pinhole/tags/PinholeTag.php';

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
 * @todo      Cache query results and clear cache when list is modified.
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

	/**
	 * Additional where clause to apply to photos in this tag list
	 *
	 * This where clause is applied in addition to where clauses specified by
	 * tags in this tag list.
	 *
	 * @see PinholeTagList::setPhotoWhereClause()
	 * @see PinholeTagList::getWhereClause()
	 */
	private $photo_where_clause = null;

	/**
	 * Order by clause to apply to photos in this tag list
	 *
	 * @see PinholeTagList::setPhotoOrderByClause()
	 */
	private $photo_order_by_clause = null;

	/**
	 * Range to apply to photos in this tag list
	 *
	 * @see PinholeTagList::setPhotoRange()
	 * @see PinholeTagList::getRange()
	 */
	private $photo_range = null;

	/**
	 * Site instance to apply to photos and tags in this tag list
	 *
	 * @see PinholeTagList::setInstance()
	 *
	 * @var PinholeInstance
	 */
	private $instance = null;

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
		$db->loadModule('Datatype', null, true);

		if (is_string($tag_list_string) && strlen($tag_list_string) > 0) {
			$tag_strings = explode('/', $tag_list_string);
			$tag_strings = array_unique($tag_strings);

			// get all simple tag strings
			$simple_tag_strings = preg_grep('/^[A-Za-z0-9]+$/', $tag_strings);
			$quoted_tag_strings =
				$db->datatype->implodeArray($simple_tag_strings, 'text');

			// load all simple tags in one query
			$sql = sprintf('select * from PinholeTag where name in (%s)',
				$quoted_tag_strings);

			$tag_data_objects =
				SwatDB::query($db, $sql, 'PinholeTagDataObjectWrapper');

			foreach ($tag_strings as $tag_string) {
				// check if we've already loaded a simple tag for this string
				$data_object = $tag_data_objects->getByIndex($tag_string);
				if ($data_object === null) {
					$tag = PinholeTagFactory::get($tag_string, $db);
					if ($tag instanceof PinholeAbstractTag) {
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
		if (strlen($string) > 0)
			$string = substr($string, 1);

		return $string;
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
			$join_clause = strtolower($join_clause);
			$join_clause = preg_replace('/\s+/', ' ', $join_clause);
		}

		// remove duplicates
		$join_clauses = array_unique($join_clauses);

		return $join_clauses;
	}

	// }}}
	// {{{ public function getWhereClause()

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

		foreach ($this as $tag) {
			$tag_where_clause = $tag->getWhereClause();
			if (strlen($tag_where_clause) > 0)
				$where_clauses[] = '('.$tag_where_clause.')';
		}

		if ($this->photo_where_clause !== null)
			$where_clauses[] = '('.$this->photo_where_clause.')';

		if ($this->instance !== null)
			$where_clauses[] = sprintf('(PinholePhoto.instance = %s)',
				$this->db->quote($this->instance->id, 'integer'));

		$where_clause = implode(' '.$operator.' ', $where_clauses);

		return $where_clause;
	}

	// }}}
	// {{{ public function getRange()

	/**
	 * Gets the database range of the tags in this tag list
	 *
	 * If multiple tags in this list define a range, the returned range is the
	 * combination of all ranges. See {@link SwatDBRange::combine()}. If an
	 * additional range is specified using the
	 * {@link PinholeTagList::setPhotoRange()} method it is also included in
	 * the combined range.
	 *
	 * @return SwatDBRange the range of the tags in this tag list or null if
	 *                      the tags in this list do not define a database
	 *                      range.
	 */
	public function getRange()
	{
		$range = $this->photo_range;

		foreach ($this as $tag) {
			$tag_range = $tag->getRange();
			if ($tag_range !== null) {
				if ($range === null) {
					$range = $tag_range;
				} else {
					$range = $range->combine($tag_range);
				}
			}
		}

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
	 * @return PinholeAbstractTag the replaced tag object or null if this list
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

		$tag_list = new PinholeTagList($this->db);

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
	 */
	public function getPhotos()
	{
		$sql = 'select PinholePhoto.* from PinholePhoto';

		$join_clauses = implode(' ', $this->getJoinClauses());
		if (strlen($join_clauses) > 0)
			$sql.= ' '.$join_clauses.' ';

		$where_clause = $this->getWhereClause();
		if (strlen($where_clause) > 0)
			$sql.= ' where '.$where_clause;

		if ($this->photo_order_by_clause !== null)
			$sql = $sql.' order by '.$this->photo_order_by_clause;

		$range = $this->getRange();
		if ($range !== null)
			$this->db->setLimit($range->getLimit(), $range->getOffset());

		$wrapper = SwatDBClassMap::get('PinholePhotoWrapper');
		return SwatDB::query($this->db, $sql, $wrapper);
	}

	// }}}
	// {{{ public function getPhotoCount()

	/**
	 * Gets the number of photos of this tag list
	 *
	 * The photo count is the number of photos in the intersection of all the
	 * tags in this list.
	 *
	 * @return integer the number of photos in this tag list.
	 */
	public function getPhotoCount()
	{
		$sql = 'select count(PinholePhoto.id) from PinholePhoto';

		$join_clauses = implode(' ', $this->getJoinClauses());
		if (strlen($join_clauses) > 0)
			$sql.= ' '.$join_clauses.' ';

		$where_clause = $this->getWhereClause();
		if (strlen($where_clause) > 0)
			$sql.= ' where '.$where_clause;

		return SwatDB::queryOne($this->db, $sql);
	}

	// }}}
	// {{{ public function getPhotoDateRange()

	/**
	 * Gets the date range of photos of this tag list
	 *
	 * @return array a two element array with the keys 'start' and 'end' and
	 *                the values being two SwatDate objects. If there are no
	 *                photos in the intersection of the tags in this tag list,
	 *                null is returned.
	 */
	public function getPhotoDateRange()
	{
		$returned_range = null;

		$sql = 'select
				max(convertTZ(PinholePhoto.photo_date, 
					PinholePhoto.photo_time_zone)) as last_photo_date,
				min(convertTZ(PinholePhoto.photo_date,
					PinholePhoto.photo_time_zone)) as first_photo_date
			from PinholePhoto';

		$join_clauses = implode(' ', $this->getJoinClauses());
		if (strlen($join_clauses) > 0)
			$sql.= ' '.$join_clauses.' ';

		$where_clause = $this->getWhereClause();
		if (strlen($where_clause) > 0)
			$sql.= ' where '.$where_clause;

		$range = SwatDB::queryRow($this->db, $sql);

		if ($range !== null) {
			$returned_range = array(
				'start' => new SwatDate($range->first_photo_date),
				'end'   => new SwatDate($range->last_photo_date),
			);
		}

		return $returned_range;
	}

	// }}}
	// {{{ public function getNextPrevPhotos()

	/**
	 * Gets the photos immediately surrounding the specified photo in this tag
	 * list
	 *
	 * @param PinholePhoto $photo the photo to get the immediately surrounding
	 *                             photos for.
	 *
	 * @return array an array containing two keys 'next' and 'prev' with the
	 *                values being PinholePhoto objects that are the next and
	 *                previous photos surrounding the specified photo. If the
	 *                specified photo does not have a previous photo, the
	 *                key 'prev' will have a value of null. Similarly, If the
	 *                specified photo does not have a next photo, the key
	 *                'next' will have a value of null.
	 */
	public function getNextPrevPhotos(PinholePhoto $photo)
	{
		$return = array(
			'next' => null,
			'prev' => null,
		);

		$sql = 'select PinholePhoto.id from PinholePhoto';

		$join_clauses = implode(' ', $this->getJoinClauses());
		if (strlen($join_clauses) > 0)
			$sql.= ' '.$join_clauses.' ';

		$where_clause = $this->getWhereClause();
		if (strlen($where_clause) > 0)
			$sql.= ' where '.$where_clause;

		if ($this->photo_order_by_clause !== null)
			$sql = $sql.' order by '.$this->photo_order_by_clause;

		$photo_class = SwatDBClassMap::get('PinholePhoto');

		// don't wrap results for speed and to save memory
		$rs = SwatDB::query($this->db, $sql, null);
		$prev_id = null;

		// look through photo ids until we find the specified photo
		while ($row = $rs->fetchRow(MDB2_FETCHMODE_OBJECT)) {
			if ($row->id === $photo->id) {
				$next_id = $rs->fetchOne();

				if ($next_id !== null) {
					$photo = new $photo_class();
					$photo->setDatabase($this->db);
					$photo->load($next_id);
					$return['next'] = $photo;
				}

				if ($prev_id !== null) {
					$photo = new $photo_class();
					$photo->setDatabase($this->db);
					$photo->load($prev_id);
					$return['prev'] = $photo;
				}

				$rs->free();
				break;
			}
			$prev_id = $row->id;
		}

		return $return;
	}

	// }}}
	// {{{ public function setPhotoWhereClause()

	/**
	 * Sets additional where clause to apply to photos in this tag list
	 *
	 * This where clause is applied in addition to where clauses specified by
	 * tags in this tag list. This can be used, for example, to only select
	 * photos that are published.
	 *
	 * @param string $where_clause the additional where clause to apply to
	 *                              photos in this tag list.
	 *
	 * @see PinholeTagList::getWhereClause()
	 */
	public function setPhotoWhereClause($where_clause)
	{
		if (is_string($where_clause))
			$this->photo_where_clause = $where_clause;
	}

	// }}}
	// {{{ public function setPhotoOrderByClause()

	/**
	 * Sets the order of photos selected by this tag list
	 *
	 * This affects the order of photos returned by
	 * {@link PinholeTagList::getPhotos()} and
	 * {@link PinholeTagList::getNextPrevPhotos()}.
	 *
	 * @param string $order_by_clause the order-by clause to apply to photos
	 *                                 selected by this tag list.
	 */
	public function setPhotoOrderByClause($order_by_clause)
	{
		if (is_string($order_by_clause))
			$this->photo_order_by_clause = $order_by_clause;
	}

	// }}}
	// {{{ public function setPhotoRange()

	public function setPhotoRange(SwatDBRange $range)
	{
		$this->photo_range = $range;
	}

	// }}}
	// {{{ public function setInstance()

	/**
	 * Sets the site instance to apply to photos and tags in this tag list
	 *
	 * If no site instance is set for this tag list, all photos and tags are
	 * included in results from this list. If an instance is set, only photos
	 * and tags belonging to the specified instance are included in results
	 * from this list.
	 *
	 * @param PinholeInstance $instance the site instance to apply to photos
	 *                                   and tags in this tag list.
	 */
	public function setInstance(PinholeInstance $instance)
	{
		$this->instance = $instance;
	}

	// }}}
	// {{{ public function getSubTags()

	/**
	 * Gets a list of tags not in this list that also apply to the photos
	 * of this list
	 *
	 * The list of subtags only includes {@link PinholeTag} objects. If this
	 * list is empty, the returned list contains all tags.
	 *
	 * @param SwatDBRange $range optional. Range of tags to retrieve. If not
	 *                            specified, all tags are loaded.
	 *
	 * @return PinholeTagList a list of tags not in this list that also apply
	 *                         to the photos of this list. The list only
	 *                         contains PinholeTag objects.
	 *
	 * @see PinholeTagList::getPhotos()
	 * @see PinholePhoto::getTags()
	 */
	public function getSubTags(SwatDBRange $range = null)
	{
		$tag_list = new PinholeTagList($this->db);

		$photo_id_sql = 'select id from PinholePhoto';

		$join_clauses = implode(' ', $this->getJoinClauses());
		if (strlen($join_clauses) > 0)
			$photo_id_sql.= ' '.$join_clauses.' ';

		$where_clause = $this->getWhereClause();
		if (strlen($where_clause) > 0)
			$photo_id_sql.= ' where '.$where_clause;

		$sql = sprintf('select PinholeTag.* from PinholeTag where id in
			(select tag from PinholePhotoTagBinding where photo in
			(%s))',
			$photo_id_sql);

		if ($this->instance !== null)
			$sql.= sprintf(' and PinholeTag.instance = %s',
				$this->db->quote($this->instance->id, 'integer'));

		// temp until ordering is possible
		$sql.= ' order by PinholeTag.title asc';

		if ($range !== null)
			$this->db->setLimit($range->getLimit(), $range->getOffset());

		$tag_data_objects = SwatDB::query($this->db, $sql,
			'PinholeTagDataObjectWrapper');

		foreach ($tag_data_objects as $data_object) {
			$tag = new PinholeTag($data_object);
			$tag_list->add($tag);
		}

		$tag_list = $tag_list->subtract($this);
		return $tag_list;
	}

	// }}}
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
	// {{{ public function load()

	/**
	 * Loads this tag list
	 *
	 * @param string $string the string to load this tag list from.
	 *
	 * @return boolean true if this tag list was loaded successfully and false
	 *                  if this tag list could not be loaded with the given
	 *                  string.
	 *
	 * @todo this will load a saved list (photo set).
	 */
	public function load($string)
	{
		// TODO: implement loading saved tag lists (photo sets).
	}

	// }}}
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
