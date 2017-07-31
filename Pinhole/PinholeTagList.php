<?php

require_once 'MDB2.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatDB/SwatDBRange.php';
require_once 'SwatDB/SwatDBRecordable.php';
require_once 'Swat/exceptions/SwatInvalidClassException.php';
require_once 'Site/dataobjects/SiteInstance.php';
require_once 'Pinhole/PinholeTagFactory.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Pinhole/dataobjects/PinholeSimplePhotoWrapper.php';
require_once 'Pinhole/dataobjects/PinholePhotoThumbnailWrapper.php';
require_once 'Pinhole/dataobjects/PinholeTagDataObjectWrapper.php';
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
	// {{{ protected properties

	protected $app;

	/**
	 * The database connection used by this tag list
	 *
	 * @var MDB2_Driver_Common
	 *
	 * @see PinholeTagList::setDatabase()
	 */
	protected $db;

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
	 * Optional cache module used by this tag list
	 *
	 * @var SiteMemcacheModule
	 */
	private $memcache;

	/**
	 * show private photos?
	 *
	 * @var boolean
	 */
	private $show_private_photos = false;

	/**
	 * show only geo-tagged photos?
	 *
	 * @var boolean
	 */
	private $show_only_geo_tagged_photos = false;

	/**
	 * Optional select clause to use in place of the default select clause
	 *
	 * @var string
	 *
	 * @see PinholeTagList::setPhotoSelectClause()
	 */
	private $photo_select_clause = null;

	/**
	 * Additional where clause to apply to photos in this tag list
	 *
	 * This where clause is applied in addition to where clauses specified by
	 * tags in this tag list.
	 *
	 * @var string
	 *
	 * @see PinholeTagList::setPhotoWhereClause()
	 * @see PinholeTagList::getWhereClause()
	 */
	private $photo_where_clause = null;

	/**
	 * Order by clause to apply to photos in this tag list
	 *
	 * @var string
	 *
	 * @see PinholeTagList::setPhotoOrderByClause()
	 */
	private $photo_order_by_clause = null;

	/**
	 * Range to apply to photos in this tag list
	 *
	 * @var SwatDBRange
	 *
	 * @see PinholeTagList::setPhotoRange()
	 * @see PinholeTagList::getRange()
	 */
	private $photo_range = null;

	/**
	 * A cache of the photos' start-date, end-date, and photo-count
	 *
	 * @var array
	 */
	private $photo_info_cache;

	/**
	 * @var boolean
	 *
	 * @see PinholeTagList::setLoadTags()
	 */
	private $load_tags = false;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new tag list
	 *
	 * @param SiteApplication $app The application using this tag list. The
	 *                             application must include a
	 *                             SiteDatabaseModule and can optionally include
	 *                             SiteMemcacheModule and
	 *                             SiteMultipleInstanceModule.
	 * @param string $tag_list_string optional. A list of tag strings separated
	 *                                 by '/' characters that are added to this
	 *                                 list when the list is created. Duplicate
	 *                                 tag strings are ignored.
	 * @param boolen $show_private_photos Whether or not to load photos marked
	 *                                 as private. Default is to not show them.
	 */
	public function __construct(
		SiteApplication $app,
		$tag_list_string = null,
		$show_private_photos = false
	) {
		$this->app = $app;
		$this->setDatabase($this->app->db);
		$this->db->loadModule('Datatype', null, true);
		$this->show_private_photos = $show_private_photos;

		//SwatDB::setDebug();
		//$this->app->memcache->flush();

		$this->setTagsByString($tag_list_string);
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
		foreach ($this->tags as $tag)
			$string.= '/'.$tag->__toString();

		// strip leading slash
		if ($string != '')
			$string = substr($string, 1);

		return $string;
	}

	// }}}
	// {{{ public function getAsList()

	/**
	 * Gets a displayable comma-list of this tag list
	 *
	 * @return string a display list string representation of this tag list.
	 */
	public function getAsList()
	{
		$list = array();
		foreach ($this->tags as $tag)
			$list[] = $tag->getTitle();

		return SwatString::toList($list);
	}

	// }}}
	// {{{ public function getEmptyCopy()

	/**
	 * Gets an empty copy of this tag list
	 *
	 * The new tag list has the same site instance, database connection and
	 * photo filtering options as this tag list.
	 *
	 * @return PinholeTagList an empty copy of this tag list.
	 */
	public function getEmptyCopy()
	{
		$tag_list = clone $this;
		$tag_list->removeAll();
		return $tag_list;
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
		foreach ($this->tags as $tag)
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

		foreach ($this->tags as $tag) {
			$tag_where_clause = $tag->getWhereClause();
			if ($tag_where_clause != '')
				$where_clauses[] = '('.$tag_where_clause.')';
		}

		if ($this->photo_where_clause !== null)
			$where_clauses[] = '('.$this->photo_where_clause.')';

		if ($this->app->getInstance() !== null)
			$where_clauses[] = sprintf('(ImageSet.instance = %s)',
				$this->db->quote($this->app->getInstanceId(), 'integer'));

		if (!$this->show_private_photos)
			$where_clauses[] = sprintf('(PinholePhoto.private = %s)',
				$this->db->quote(false, 'boolean'));

		if ($this->show_only_geo_tagged_photos) {
			$where_clauses[] = sprintf('(PinholePhoto.gps_latitude is not null
				and PinholePhoto.gps_longitude is not null)');
		}

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

		foreach ($this->tags as $tag) {
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

		$tag_list = $this->getEmptyCopy();

		foreach ($this->tags as $tag)
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
	public function getPhotos(
		$dimension_shortname = null,
		array $extra_fields = null
	) {
		if ($dimension_shortname == 'thumbnail')
			$wrapper = SwatDBClassMap::get('PinholePhotoThumbnailWrapper');
		elseif ($dimension_shortname === false)
			$wrapper = SwatDBClassMap::get('PinholeSimplePhotoWrapper');
		else
			$wrapper = SwatDBClassMap::get('PinholePhotoWrapper');

		$photos = false;

		$args = func_get_args();
		$key = $this->getCacheKey(__FUNCTION__, $args);
		$photos = $this->app->getCacheRecordset($key, $wrapper, 'photos');

		if ($photos === false) {
			$sql = sprintf('select %s from PinholePhoto
				inner join ImageSet on PinholePhoto.image_set = ImageSet.id',
				$this->getPhotoSelectClause($extra_fields));

			$join_clauses = implode(' ', $this->getJoinClauses());
			if ($join_clauses != '')
				$sql.= ' '.$join_clauses.' ';

			$where_clause = $this->getWhereClause();
			if ($where_clause != '')
				$sql.= ' where '.$where_clause;

			$sql.= ' order by '.$this->getPhotoOrderByClause();

			$range = $this->getRange();

			if ($range !== null)
				$this->db->setLimit($range->getLimit(), $range->getOffset());

			$photos = SwatDB::query($this->db, $sql, $wrapper);

			if ($this->load_tags)
				$this->loadPhotoTags($photos);

			$this->app->addCacheRecordset($photos, $key, 'photos');
		}

		return $photos;
	}

	// }}}
	// {{{ public function getGpsData()

	/**
	 * Gets just the GPS data for a set of photos
	 *
	 * @return SwatDBDefaultRecordsetWrapper the gps coordinates for the
	 *                                       photos in this list.
	 */
	public function getGpsData()
	{
		$args = func_get_args();
		$key = $this->getCacheKey(__FUNCTION__, $args);
		$wrapper = 'SwatDBDefaultRecordsetWrapper';
		$photos = $this->app->getCacheRecordset($key, $wrapper, 'photos');

		if ($photos === false) {
			$sql = 'select PinholePhoto.id, PinholePhoto.gps_latitude,
					PinholePhoto.gps_longitude
				from PinholePhoto
				inner join ImageSet on PinholePhoto.image_set = ImageSet.id';

			$join_clauses = implode(' ', $this->getJoinClauses());
			if ($join_clauses != '')
				$sql.= ' '.$join_clauses.' ';

			$where_clause = $this->getWhereClause();
			if ($where_clause != '')
				$sql.= ' where '.$where_clause;

			$photos = SwatDB::query($this->db, $sql, $wrapper);
			$this->app->addCacheRecordset($photos, $key, 'photos');
		}

		return $photos;
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
		$info = $this->getPhotoInfo();
		if (count($info) > 0)
			return $info['count'];
		else
			return 0;
	}

	// }}}
	// {{{ public function getPhotoCountByDate()

	/**
	 * Gets a summary of the number of photos in this tag list indexed
	 * and grouped by the specified date part
	 *
	 * @param string $date_part the date part with which to index and group
	 *                           photo counts.
	 *
	 * @return array an array indexed by the relevant date part with values
	 *                indicating the number of photos in the tag list
	 *                for the date part index. If the tag list has no photos
	 *                on a specific date, the returned array does not contain
	 *                an index at that date.
	 */
	public function getPhotoCountByDate($date_part)
	{
		$args = func_get_args();
		$cache_key = $this->getCacheKey(__FUNCTION__, $args);
		$value = $this->app->getCacheValue($cache_key, 'photos');
		if ($value !== false)
			return $value;

		$group_by_parts = array();

		switch ($date_part) {
		case 'day' :
			$group_by_parts[] = 'day';
			$group_by_parts[] = 'month';
			$group_by_parts[] = 'year';
			$date_format = 'yyyy-MM-dd';
			break;

		case 'month' :
			$group_by_parts[] = 'month';
			$group_by_parts[] = 'year';
			$date_format = 'yyyy-MM';
			break;

		case 'year' :
			$group_by_parts[] = 'year';
			$date_format = 'yyyy';
			break;
		}

		$group_by_clause = '';

		$count = 0;
		foreach ($group_by_parts as $part) {
			if ($count > 0)
				$group_by_clause.= ', ';

			$group_by_clause.= sprintf(
				'date_part(%s, convertTZ(PinholePhoto.photo_date,
				PinholePhoto.photo_time_zone))',
				$this->db->quote($part, 'text'));

			$count++;
		}

		$sql = 'select
				count(PinholePhoto.id) as photo_count,
				max(convertTZ(PinholePhoto.photo_date,
				PinholePhoto.photo_time_zone)) as photo_date
			from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id';

		$join_clauses = implode(' ', $this->getJoinClauses());
		if ($join_clauses != '')
			$sql.= ' '.$join_clauses.' ';

		$where_clause = $this->getWhereClause();
		if ($where_clause != '')
			$sql.= ' where '.$where_clause;

		if ($group_by_clause != '')
			$sql.= ' group by '.$group_by_clause;

		$rows = SwatDB::query($this->db, $sql, null);

		$dates = array();
		while ($row = $rows->fetchRow(MDB2_FETCHMODE_OBJECT)) {
			if ($row->photo_date === null)
				continue;

			$date = new SwatDate($row->photo_date);
			$dates[$date->formatLikeIntl($date_format)] = $row->photo_count;
		}

		$this->app->addCacheValue($dates, $cache_key, 'photos');

		return $dates;
	}

	// }}}
	// {{{ public function getPhotoInfo()

	/**
	 * Gets the date range and count of photos of this tag list
	 *
	 * @return array a three element array with the keys 'start', 'end', and
	 *                'count'. If there are no photos in the intersection of
	 *                the tags in this tag list, an empty array is returned.
	 */
	public function getPhotoInfo()
	{
		$args = func_get_args();
		$cache_key = $this->getCacheKey(__FUNCTION__, $args);
		$value = $this->app->getCacheValue($cache_key, 'photos');
		if ($value !== false)
			return $value;

		if (is_array($this->photo_info_cache))
			return $this->photo_info_cache;

		$sql = 'select count(PinholePhoto.id) as photo_count,
				max(convertTZ(PinholePhoto.photo_date,
					PinholePhoto.photo_time_zone)) as last_photo_date,
				min(convertTZ(PinholePhoto.photo_date,
					PinholePhoto.photo_time_zone)) as first_photo_date,
				sum(case when PinholePhoto.gps_latitude is not null
					and PinholePhoto.gps_longitude is not null
					then 1 else 0 end) as gps_photo_count,
				max(PinholePhoto.gps_latitude) as max_latitude,
				max(PinholePhoto.gps_longitude) as max_longitude,
				min(PinholePhoto.gps_latitude) as min_latitude,
				min(PinholePhoto.gps_longitude) as min_longitude
			from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id';

		$join_clauses = implode(' ', $this->getJoinClauses());
		if ($join_clauses != '')
			$sql.= ' '.$join_clauses.' ';

		$where_clause = $this->getWhereClause();
		if ($where_clause != '')
			$sql.= ' where '.$where_clause;

		$row = SwatDB::queryRow($this->db, $sql);

		if ($row === null) {
			$this->photo_info_cache = array();
		} else {
			$this->photo_info_cache = array(
				'count' => $row->photo_count,
				'gps_count' => $row->gps_photo_count,
				'max_latitude' => $row->max_latitude,
				'max_longitude' => $row->max_longitude,
				'min_latitude' => $row->min_latitude,
				'min_longitude' => $row->min_longitude,
				'start' => new SwatDate($row->first_photo_date),
				'end'   => new SwatDate($row->last_photo_date),
			);
		}

		$this->app->addCacheValue($this->photo_info_cache,
			$cache_key, 'photos');

		return $this->photo_info_cache;
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
		$args = func_get_args();
		$cache_key = $this->getCacheKey(__FUNCTION__, $args);
		$value = $this->app->getCacheValue($cache_key, 'photos');
		if ($value !== false)
			return $value;

		$sql = 'select
				max(convertTZ(PinholePhoto.photo_date,
					PinholePhoto.photo_time_zone)) as last_photo_date,
				min(convertTZ(PinholePhoto.photo_date,
					PinholePhoto.photo_time_zone)) as first_photo_date
			from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id';

		$join_clauses = implode(' ', $this->getJoinClauses());
		if ($join_clauses != '')
			$sql.= ' '.$join_clauses.' ';

		$where_clause = $this->getWhereClause();
		if ($where_clause != '')
			$sql.= ' where '.$where_clause;

		$range = SwatDB::queryRow($this->db, $sql);

		if ($range !== null) {
			$returned_range = array(
				'start' => new SwatDate($range->first_photo_date),
				'end'   => new SwatDate($range->last_photo_date),
			);
		} else {
			$returned_range = null;
		}

		$this->app->addCacheValue($returned_range, $cache_key, 'photos');

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
		$cache_key = $this->getCacheKey(__FUNCTION__, array($photo->id));
		$value = $this->app->getCacheValue($cache_key, 'photos');
		if ($value !== false) {
			if ($value['next'] !== null)
				$value['next']->setDatabase($this->db);

			if ($value['prev'] !== null)
				$value['prev']->setDatabase($this->db);

			return $value;
		}

		$return = array(
			'next' => null,
			'prev' => null,
		);

		$sql = 'select PinholePhoto.id from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id';

		$join_clauses = implode(' ', $this->getJoinClauses());
		if ($join_clauses != '')
			$sql.= ' '.$join_clauses.' ';

		$where_clause = $this->getWhereClause();
		if ($where_clause != '')
			$sql.= ' where '.$where_clause;

		$sql.= ' order by '.$this->getPhotoOrderByClause();

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

		$this->app->addCacheValue($return, $cache_key, 'photos');

		return $return;
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
	 *                           specified, all tags are loaded.
	 * @param string $order_by_clause optional. SQL order by clause of the tag
	 *                                list.
	 *
	 * @return PinholeTagList a list of tags not in this list that also apply
	 *                         to the photos of this list. The list only
	 *                         contains PinholeTag objects.
	 *
	 * @see PinholeTagList::getPhotos()
	 */
	public function getSubTags(
		SwatDBRange $range = null,
		$order_by_clause = null
	) {
		$tag_data_objects = $this->getSubTagDataObjects($range,
			$order_by_clause);

		$tag_list = $this->getEmptyCopy();

		foreach ($tag_data_objects as $data_object) {
			$tag = new PinholeTag($this->app->getInstance(), $data_object);
			$tag_list->add($tag);
		}

		$tag_list = $tag_list->subtract($this);

		return $tag_list;
	}

	// }}}
	// {{{ public function getSubTagsByPopularity()

	/**
	 * Gets a list of tags not in this list that also apply to the photos
	 * of this list and orders them by the number of photos
	 *
	 * The list of subtags only includes {@link PinholeTag} objects. If this
	 * list is empty, the returned list contains all tags.
	 *
	 * @param SwatDBRange $range optional. Range of tags to retrieve. If not
	 *                           specified, all tags are loaded.
	 * @param string $order_by_clause optional. SQL order by clause of the tag
	 *                                list.
	 *
	 * @return PinholeTagList a list of tags not in this list that also apply
	 *                         to the photos of this list. The list only
	 *                         contains PinholeTag objects.
	 *
	 * @see PinholeTagList::getSubTags()
	 */
	public function getSubTagsByPopularity(
		SwatDBRange $range = null,
		$order_by_clause = null
	) {
		$args = func_get_args();
		$cache_key = $this->getCacheKey(__FUNCTION__, $args);
		$value = $this->app->getCacheValue($cache_key, 'photos');
		if ($value !== false)
			return $value;

		$tag_list = $this->getEmptyCopy();

		$sql = sprintf('select count(PinholePhoto.id) as photo_count,
				PinholePhotoTagBinding.tag
			from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			inner join PinholePhotoTagBinding on
				PinholePhotoTagBinding.photo = PinholePhoto.id
			%s
			where %s
			group by PinholePhotoTagBinding.tag
			order by photo_count desc',
			implode(' ', $this->getJoinClauses()),
			$this->getWhereClause());

		if ($range !== null)
			$this->db->setLimit($range->getLimit(), $range->getOffset());

		$popular_tags = SwatDB::query($this->db, $sql, null);

		$tag_ids = array();
		while ($tag = $popular_tags->fetchRow(MDB2_FETCHMODE_OBJECT))
			$tag_ids[] = $this->db->quote($tag->tag, 'integer');

		if (count($tag_ids) == 0)
			return $tag_list;

		$sql = sprintf('select PinholeTag.* from PinholeTag where id in (%s)',
			implode(',', $tag_ids));

		if ($order_by_clause !== null)
			$sql.= ' order by '.$order_by_clause;

		$tag_data_objects = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('PinholeTagDataObjectWrapper'));

		$popular_tags->seek();
		while ($row = $popular_tags->fetchRow(MDB2_FETCHMODE_OBJECT)) {
			foreach ($tag_data_objects as $data_object) {
				if ($data_object->id == $row->tag) {
					$tag = new PinholeTag($this->app->getInstance(),
						$data_object);

					$tag->photo_count = $row->photo_count;
					$tag_list->add($tag);
				}
			}
		}

		$tag_list = $tag_list->subtract($this);

		// don't use memcache convenience method here
		if (isset($this->app->memcache))
			$this->app->memcache->setNs('photos', $cache_key, $tag_list);

		return $tag_list;
	}

	// }}}
	// {{{ public function getSubTagCount()

	/**
	 * Gets a count of tags not in this list that also apply to the photos
	 * of this list
	 *
	 * @return integer Number of sub tags
	 *
	 * @see PinholePhoto::getSubTags()
	 */
	public function getSubTagCount()
	{
		$args = func_get_args();
		$cache_key = $this->getCacheKey(__FUNCTION__, $args);
		$count = $this->app->getCacheValue($cache_key, 'photos');

		if ($count === false) {
			$sql = sprintf('select count(PinholeTag.id)
				from PinholeTag where %s',
				$this->getSubTagWhereClause());

			$count = SwatDB::queryOne($this->db, $sql);
			$this->app->addCacheValue($count, $cache_key, 'photos');
		}

		return $count - count($this);
	}

	// }}}
	// {{{ public function getGeoTaggedPhotoCount()

	/**
	 * Gets the number of photos in this list that have gps data
	 *
	 * @return integer The number of photos with gps data
	 */
	public function getGeoTaggedPhotoCount()
	{
		$info = $this->getPhotoInfo();
		if (count($info) > 0)
			return $info['gps_count'];
		else
			return 0;
	}

	// }}}
	// {{{ protected function setTagsByString()

	/**
	 * Get tags from string
	 *
	 * @param string $tag_list_string A list of tag strings separated
	 *                                by '/' characters that are added to this
	 *                                list when the list is created. Duplicate
	 *                                tag strings are ignored.
	 */
	protected function setTagsByString($tag_list_string = null)
	{
		if (!is_string($tag_list_string) || $tag_list_string == '')
			return;

		$tag_strings = explode('/', $tag_list_string);

		// remove duplicate tags
		$tag_strings = array_unique($tag_strings);

		// get all simple tag strings
		$simple_tag_strings = preg_grep('/^[A-Za-z0-9]+$/', $tag_strings);

		if (count($simple_tag_strings) > 0) {
			// sort array so it can be used as a cache key
			sort($simple_tag_strings);

			$quoted_tag_strings = $this->db->datatype->implodeArray(
				$simple_tag_strings, 'text');

			$cache_key = $this->getCacheKey('loadSimpleTags',
				$simple_tag_strings);

			$tag_data_objects = $this->app->getCacheValue(
				$cache_key, 'photos');

			if ($tag_data_objects === false) {
				$instance_id = ($this->app->getInstance() === null) ?
					null : $this->app->getInstanceId();

				// load all simple tags in one query
				$sql = sprintf('select * from PinholeTag
					where name in (%s) and instance %s %s',
					$quoted_tag_strings,
					SwatDB::equalityOperator($instance_id),
					$this->db->quote($instance_id, 'integer'));

				$tag_data_objects = SwatDB::query($this->db, $sql,
					SwatDBClassMap::get('PinholeTagDataObjectWrapper'));

				$this->app->addCacheValue($tag_data_objects,
					$cache_key, 'photos');
			}
		} else {
			$tag_data_objects = new PinholeTagDataObjectWrapper();
		}

		foreach ($tag_strings as $tag_string) {
			// check if we've already loaded a simple tag for this string
			$data_object = $tag_data_objects->getByIndex($tag_string);
			if ($data_object === null) {
				$tag = PinholeTagFactory::get($tag_string, $this->db,
					$this->app->getInstance());

				if ($tag instanceof PinholeAbstractTag) {
					$this->add($tag);
				}
			} else {
				$tag = new PinholeTag($this->app->getInstance(), $data_object);
				$this->add($tag);
			}
		}
	}

	// }}}
	// {{{ protected function getPhotoSelectClause()

	protected function getPhotoSelectClause(array $extra_fields = null)
	{
		$clause = '';

		if ($this->photo_select_clause === null) {
			$fields = array('PinholePhoto.id', 'PinholePhoto.title',
				'PinholePhoto.original_filename', 'PinholePhoto.photo_date',
				'PinholePhoto.publish_date', 'PinholePhoto.image_set',
				'PinholePhoto.filename', 'PinholePhoto.status');

			if ($extra_fields !== null)
				$fields = array_merge($fields, $extra_fields);

			$clause = implode(', ', $fields);
		} else {
			$clause = $this->photo_select_clause;
		}

		return $clause;
	}

	// }}}

	// modifiers
	// {{{ public function setPhotoSelectClause()

	/**
	 * Optionally override the default select clause
	 *
	 * @param string $select_clause Query select clause.
	 */
	public function setPhotoSelectClause($select_clause)
	{
		if (is_string($select_clause))
			$this->photo_select_clause = $select_clause;
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
	// {{{ public function setLoadTags()

	/**
	 * Sets whether or not to efficiently load the tags for loaded photos
	 *
	 * Set this to true if the tags are to be displayed, false otherwise.
	 *
	 * @param boolean $load_tags
	 */
	public function setLoadTags($load_tags)
	{
		$this->load_tags = (boolean)$load_tags;
	}

	// }}}
	// {{{ public function setShowOnlyGeoTaggedPhotos()

	/**
	 * Sets whether to only show geo-tagged photos
	 *
	 * @param boolean $show_only_geo_tagged_photos
	 */
	public function setShowOnlyGeoTaggedPhotos($show_only_geo_tagged_photos)
	{
		$this->show_only_geo_tagged_photos = $show_only_geo_tagged_photos;
	}

	// }}}

	// set manipulation
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
			$tag = PinholeTagFactory::get($tag, $this->db,
				$this->app->getInstance());
		} elseif ($tag instanceof PinholeTagDataObject) {
			$tag = new PinholeTag($this->app->getInstance(), $tag);
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

			$this->resetPhotoInfoCache();
		}

		return $removed;
	}

	// }}}
	// {{{ public function removeAll()

	/**
	 * Removes all tags from this tag list
	 *
	 * @return PinholeTagList the removed tags of this list or a new empty tag
	 *                          list if this list does not contain any tags.
	 */
	public function removeAll()
	{
		// returned tag list is just a copy of this tag list
		$tag_list = clone $this;

		// remove all tags from this list efficiently
		$this->tags = array();
		$this->tag_keys = array();
		$this->tag_index = 0;

		$this->resetPhotoInfoCache();

		return $tag_list;
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
		$tag_list = $this->getEmptyCopy();

		foreach ($this->tags as $tag) {
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
		$new_tag_list = $this->getEmptyCopy();

		foreach ($this->tags as $tag)
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

	// recordable interface
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
		foreach ($this->tags as $tag)
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
		foreach ($this->tags as $tag)
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
		foreach ($this->tags as $tag)
			$tag->delete();

		$this->resetPhotoInfoCache();
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
		foreach ($this->tags as $tag) {
			if ($tag->isModified()) {
				$modified = true;
				break;
			}
		}
		return $modified;
	}

	// }}}

	// iterator interface
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

	// countable interface
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

	// helper methods
	// {{{ protected function resetPhotoInfoCache()

	protected function resetPhotoInfoCache()
	{
		$this->photo_info_cache = null;
	}

	// }}}
	// {{{ protected function loadPhotoTags()

	/**
	 * Efficiently loads tags for a set of photos
	 *
	 * @param PinholePhotoWrapper $photos the photos for which to efficiently load
	 *                                 tags.
	 */
	protected function loadPhotoTags(PinholePhotoWrapper $photos)
	{
		$instance_id = ($this->app->getInstance() === null) ?
			null : $this->app->getInstanceId();

		$wrapper = SwatDBClassMap::get('PinholeTagDataObjectWrapper');

		// get photo ids
		$photo_ids = array();
		foreach ($photos as $photo) {
			$photo_ids[] = $photo->id;
		}
		$photo_ids = $this->db->implodeArray($photo_ids, 'integer');

		// build SQL to select all tags
		$sql = sprintf('select PinholeTag.*, PinholePhotoTagBinding.photo
			from PinholeTag
				inner join PinholePhotoTagBinding on
					PinholeTag.id = PinholePhotoTagBinding.tag
			where photo in (%s) and PinholeTag.instance %s %s
			order by photo, createdate desc',
			$photo_ids,
			SwatDB::equalityOperator($instance_id),
			$this->db->quote($instance_id, 'integer'));

		// get all tags
		$tags = SwatDB::query($this->db, $sql, $wrapper);

		// assign tags to correct photos
		$current_photo_id = null;
		$current_recordset = null;
		foreach ($tags as $tag) {
			$photo_id = $tag->getInternalValue('photo');

			if ($photo_id !== $current_photo_id) {
				$current_photo_id = $photo_id;
				$current_recordset = new $wrapper();
				$photos[$photo_id]->tags = new $wrapper();
			}

			$photos[$photo_id]->tags->add($tag);
		}
	}

	// }}}
	// {{{ protected function getCacheKey()

	protected function getCacheKey($method_name, array $args = array())
	{
		/*
		both (string)$this and the tag get var are needed to make the cache
		key unique. We need the get var for page tags that are removed, and
		$this->tags for when the tag list is duplicated and manipulated.
		*/

		$tags = SiteApplication::initVar('tags');
		return sprintf('PinholeTagList.%s.%s.%s.%s.%s',
			(string)$this, (string)$tags,
			$method_name, md5(serialize($args)),
			$this->show_private_photos ? 'private' : 'public');
	}

	// }}}
	// {{{ private function getPhotoOrderByClause()

	/**
	 * Gets the order SQL order-by clause for this list
	 *
	 * @return string the SQL order-by clause
	 */
	private function getPhotoOrderByClause()
	{
		if ($this->photo_order_by_clause !== null) {
			$order_by = $this->photo_order_by_clause;
		} else {
			$order_by = 'PinholePhoto.publish_date desc,
				PinholePhoto.photo_date asc, PinholePhoto.id';

			// If all of the tags are date tags, order by photo_date rather
			// than publish_date
			if (count($this->tags) != 0) {
				$all_date_tags = true;
				foreach ($this->tags as $tag) {
					if (!$tag instanceof PinholeDateTag) {
						$all_date_tags = false;
						break;
					}
				}

				if ($all_date_tags)
					$order_by = 'coalesce(PinholePhoto.photo_date,
						PinholePhoto.publish_date) asc, id asc';
			}

			// If there are any event tags in this tag list, order by
			// photo date asecending.
			foreach ($this->getByType('PinholeTag') as $tag) {
				if ($tag->event) {
					$order_by = 'coalesce(PinholePhoto.photo_date,
						PinholePhoto.publish_date) asc, id asc';

					break;
				}
			}

			foreach ($this->getByType('PinholeTag') as $tag) {
				if ($tag->order_manually) {
					$order_by = 'tag'.$tag->id.'.displayorder, '.$order_by;
					break;
				}
			}
		}

		return $order_by;
	}

	// }}}
	// {{{ private function getSubTagWhereClause()

	private function getSubTagWhereClause()
	{
		$photo_id_sql = 'select PinholePhoto.id from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id';

		$join_clauses = implode(' ', $this->getJoinClauses());
		if ($join_clauses != '')
			$photo_id_sql.= ' '.$join_clauses.' ';

		$where_clause = $this->getWhereClause();
		if ($where_clause != '')
			$photo_id_sql.= ' where '.$where_clause;

		$sql = sprintf('PinholeTag.id in
			(select tag from PinholePhotoTagBinding where photo in
			(%s))',
			$photo_id_sql);

		if ($this->app->getInstance() !== null)
			$sql.= sprintf(' and PinholeTag.instance = %s',
				$this->db->quote($this->app->getInstanceId(), 'integer'));

		return $sql;
	}

	// }}}
	// {{{ private function getSubTagDataObjects()

	/**
	 * Gets a recordset of tag dataobjects.
	 *
	 * @param SwatDBRange $range optional. Range of tags to retrieve. If not
	 *                           specified, all tags are loaded.
	 * @param string $order_by_clause optional. SQL order by clause of the tag
	 *                                list.
	 *
	 * @return PinholeTagDataObjectWrapper
	 */
	private function getSubTagDataObjects(
		SwatDBRange $range = null,
		$order_by_clause = null
	) {
		$args = func_get_args();
		$cache_key = $this->getCacheKey(__FUNCTION__, $args);
		$value = $this->app->getCacheRecordset($cache_key,
			'PinholeTagDataObjectWrapper', 'photos');

		if ($value !== false)
			return $value;

		if ($order_by_clause === null)
			$order_by_clause = 'PinholeTagDateView.first_modified desc';

		$sql = sprintf('select PinholeTag.*,
				PinholeTagDateView.first_modified,
				PinholeTagDateView.last_modified
			from PinholeTag
			inner join PinholeTagDateView on
				PinholeTagDateView.tag = PinholeTag.id
			where %s
			order by %s',
			$this->getSubTagWhereClause(),
			$order_by_clause);

		if ($range !== null)
			$this->db->setLimit($range->getLimit(), $range->getOffset());

		$tag_data_objects = SwatDB::query($this->db, $sql,
			'PinholeTagDataObjectWrapper');

		$this->app->addCacheRecordset($tag_data_objects, $cache_key, 'photos');
		return $tag_data_objects;
	}

	// }}}
}

?>
