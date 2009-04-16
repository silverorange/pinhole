<?php

require_once 'Date/Calc.php';
require_once 'Swat/SwatDate.php';
require_once 'Swat/exceptions/SwatException.php';
require_once 'Site/dataobjects/SiteImage.php';
require_once 'Pinhole/dataobjects/PinholeImageSet.php';
require_once 'Pinhole/dataobjects/PinholeImageDimensionWrapper.php';
require_once 'Pinhole/dataobjects/PinholePhotoDimensionBindingWrapper.php';
require_once 'Pinhole/dataobjects/PinholePhotoMetaDataBindingWrapper.php';
require_once 'Pinhole/dataobjects/PinholeTagDataObjectWrapper.php';
require_once 'Pinhole/exceptions/PinholeUploadException.php';
require_once 'Pinhole/exceptions/PinholeProcessingException.php';

/**
 * A dataobject class for photos
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhoto extends SiteImage
{
	// {{{ constants

	const STATUS_UNPROCESSED = 3;
	const STATUS_PENDING     = 0;
	const STATUS_PUBLISHED   = 1;
	const STATUS_UNPUBLISHED = 2;

	const DATE_PART_YEAR     = 1;
	const DATE_PART_MONTH    = 2;
	const DATE_PART_DAY      = 4;
	const DATE_PART_TIME     = 8;

	// }}}
	// {{{ public properties

	/**
	 * Upload date
	 *
	 * The date the photo was uploaded
	 *
	 * @var Date
	 */
	public $upload_date;

	/**
	 * Temp filename
	 *
	 * The filename of the temporary file used for processing
	 *
	 * @var string
	 */
	public $temp_filename;

	/**
	 * Raw exif data
	 *
	 * A serialized string containing the raw exif data stored with the
	 * photo. The returned value of exif_read_data().
	 *
	 * @var string
	 */
	public $serialized_exif;

	/**
	 * Photo date
	 *
	 * The date the photo was taken.
	 *
	 * @var Date
	 */
	public $photo_date;

	/**
	 * Date parts to display
	 *
	 * A bitwise value made up of PinholePhoto::DATE_PART* constants.
	 *
	 * @var integer
	 */
	public $photo_date_parts;

	/**
	 * Publish date
	 *
	 * The date the photo's status was published. See
	 * {@link PinholePhoto::publish()}
	 *
	 * @var Date
	 */
	public $publish_date;

	/**
	 * Visibility status
	 *
	 * Set using class contstants:
	 * STATUS_UNPROCESSED - uploaded but not yet procesed
	 * STATUS_PENDING - processed but not yet added to the site
	 * STATUS_PUBLISHED - photo info added and shown on site
	 * STATUS_UNPUBLISHED - not shown on the site
	 *
	 * @var integer
	 */
	public $status;

	/**
	 * Time-zone of the photo
	 *
	 * @var string
	 */
	 public $photo_time_zone;

	/**
	 * Private
	 *
	 * @var boolean
	 */
	 public $private;

	/**
	 * For sale
	 *
	 * @var boolean
	 */
	 public $for_sale;

	// }}}
	// {{{ protected properties

	/**
	 * The instance for this photo - only used for processing.
	 *
	 * @var SiteInstance
	 */
	protected $instance;

	protected $selectable_dimensions;

	/**
	 * Cache of tags for this photo
	 *
	 * @var PinholeTagDataObjectWrapper
	 *
	 * @see PinholePhoto::getTags()
	 * @see PinholePhoto::setTags()
	 */
	protected $tags_cache;

	// }}}

	// dataobject methods
	// {{{ public function getTags()

	/**
	 * Gets tags for this photo
	 *
	 * @return PinholeTagDataObjectWrapper
	 */
	public function getTags()
	{
		if ($this->tags_cache === null) {
			$this->tags_cache = $this->tags;
		}

		return $this->tags_cache;
	}

	// }}}
	// {{{ public function setTags()

	/**
	 * Sets tags files for this photo
	 *
	 * Allows a single query to set tag sets for multiple photos.
	 *
	 * @param PinholeTagDataObjectWrapper $photos
	 */
	public function setTags(PinholeTagDataObjectWrapper $tags)
	{
		$this->tags_cache = $tags;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->table = 'PinholePhoto';
		$this->image_set_shortname = 'photos';

		$this->registerInternalProperty('image_set',
			SwatDBClassMap::get('PinholeImageSet'));

		$this->registerInternalProperty('photographer',
			SwatDBClassMap::get('PinholePhotographer'));

		$this->registerDateProperty('upload_date');
		$this->registerDateProperty('publish_date');
		$this->registerDateProperty('photo_date');
	}

	// }}}
	// {{{ protected function getImageDimensionBindingClassName()

	protected function getImageDimensionBindingClassName()
	{
		return SwatDBClassMap::get('PinholePhotoDimensionBinding');
	}

	// }}}
	// {{{ protected function getImageDimensionBindingWrapperClassName()

	protected function getImageDimensionBindingWrapperClassName()
	{
		return SwatDBClassMap::get('PinholePhotoDimensionBindingWrapper');
	}

	// }}}
	// {{{ protected function getSerializableSubDataObjects()

	protected function getSerializableSubDataObjects()
	{
		return array_merge(parent::getSerializableSubDataObjects(), array(
			'image_set',
			'photographer',
			'dimension_bindings',
			'tags',
			'meta_data',
		));
	}

	// }}}
	// {{{ protected function getSerializablePrivateProperties()

	protected function getSerializablePrivateProperties()
	{
		return array_merge(parent::getSerializablePrivateProperties(), array(
			'tags_cache',
		));
	}

	// }}}

	// image methods
	// {{{ public function publish()

	/**
	 * Publishes this photo
	 *
	 * @param boolean $set_publish_date
	 */
	public function publish($set_publish_date = true)
	{
		if ($set_publish_date)
			$this->publish_date = new SwatDate();

		$this->status = self::STATUS_PUBLISHED;

		$this->save();
	}

	// }}}
	// {{{ public function getUri()

	public function getUri($shortname, $prefix = null)
	{
		$dimension = $this->image_set->getDimensionByShortname(
			$shortname);

		if ($dimension->publicly_accessible) {
			return parent::getUri($shortname, $prefix);
		} else {
			$uri = sprintf('loadphoto/%s/%s',
				$dimension->shortname,
				$this->getFilename($shortname));

			if ($prefix !== null)
				$uri = $prefix.$uri;

			return $uri;
		}
	}

	// }}}
	// {{{ public function getFilePath()

	public function getFilePath($shortname)
	{
		$dimension = $this->image_set->getDimensionByShortname(
			$shortname);

		return sprintf('%s/%s/%s/%s',
			$this->getFileBase(),
			($dimension->publicly_accessible) ? 'public' : 'private',
			$dimension->shortname,
			$this->getFilename($shortname));
	}

	// }}}
	// {{{ public function getTitle()

	/**
	 * Gets the title of this photo
	 *
	 * @param boolean $show_filename Whether to show the photo's filename if
	 *                                no title is set.
	 *
	 * @return string the title of this photo. If this photo has no title then
	 *                 the original filename is returned.
	 */
	public function getTitle($show_filename = false)
	{
		$title = $this->title;

		if ($this->title === null && $show_filename)
			$title = $this->original_filename;

		return $title;
	}

	// }}}
	// {{{ public function setInstance()

	/**
	 * Sets the instance for this photo
	 *
	 * Not for reading, only used for processing.
	 *
	 * param SiteInstance $instance The instance for this photo
	 */
	public function setInstance(SiteInstance $instance = null)
	{
		$this->instance = $instance;
	}

	// }}}
	// {{{ public function addTagsByName()

	public function addTagsByName(array $tag_names,
		$clear_existing_tags = false)
	{
		$this->checkDB();

		$instance_id = ($this->instance === null) ? null : $this->instance->id;
		$tag_names = array_keys($tag_names);

		$sql = sprintf('delete from PinholePhotoTagBinding
			where photo = %s',
			$this->db->quote($this->id, 'integer'));

		if (!$clear_existing_tags)
			$sql.= sprintf(' and tag in (select id from
				PinholeTag where name in (%s) and instance %s %s)',
				$this->db->datatype->implodeArray($tag_names, 'text'),
				SwatDB::equalityOperator($instance_id),
				$this->db->quote($instance_id, 'integer'));

		SwatDB::exec($this->db, $sql);

		$sql = sprintf('insert into PinholePhotoTagBinding
			(photo, tag) select %1$s, id from PinholeTag
			where name in (%2$s) and PinholeTag.instance %3$s %4$s',
			$this->db->quote($this->id, 'integer'),
			$this->db->datatype->implodeArray($tag_names, 'text'),
			SwatDB::equalityOperator($instance_id),
			$this->db->quote($instance_id, 'integer'));

		SwatDB::exec($this->db, $sql);
	}

	// }}}
	// {{{ public function setStatus()

	public function setStatus($status)
	{
		// make static so that multiple photos being published at the same time
		// have the exact same publish date
		static $publish_date;

		if (!array_key_exists($status, self::getStatuses()))
			throw new SwatException('Invalid Status');

		if ($status == self::STATUS_PUBLISHED &&
			$this->status != self::STATUS_PUBLISHED) {

			if ($publish_date === null) {
				$publish_date = new SwatDate();
				$publish_date->toUTC();
			}

			$this->publish_date = clone $publish_date;
		}

		$this->status = $status;
	}

	// }}}
	// {{{ public function isPublished()

	public function isPublished()
	{
		return ($this->status == self::STATUS_PUBLISHED);
	}

	// }}}
	// {{{ public function getClosestSelectableDimensionTo()

	public function getClosestSelectableDimensionTo($shortname)
	{
		$dimensions = $this->getSelectableDimensions();
		$display_dimension = null;

		foreach ($dimensions as $dimension)
			if ($dimension->shortname == $shortname)
				$display_dimension = $dimension;

		if ($display_dimension === null)
			return $dimensions->getFirst();
		else
			return $display_dimension;
	}

	// }}}
	// {{{ public function getSelectableDimensions()

	public function getSelectableDimensions()
	{
		if ($this->selectable_dimensions === null) {
			$sql = sprintf('select ImageDimension.*
					from PinholePhotoDimensionBinding
					inner join ImageDimension on
						PinholePhotoDimensionBinding.dimension =
							ImageDimension.id
					where PinholePhotoDimensionBinding.photo = %s
						and ImageDimension.selectable = %s
					order by coalesce(ImageDimension.max_width,
						ImageDimension.max_height) asc',
				$this->db->quote($this->id, 'integer'),
				$this->db->quote(true, 'boolean'));

			$wrapper = SwatDBClassMap::get('PinholeImageDimensionWrapper');

			$dimensions = SwatDB::query($this->db, $sql, $wrapper);

			$this->selectable_dimensions = new $wrapper();
			$last_dimension = null;

			foreach ($dimensions as $dimension) {
				if ($last_dimension === null ||
					$this->getWidth($dimension->shortname) >
					$this->getWidth($last_dimension->shortname) * 1.1) {

					$this->selectable_dimensions->add($dimension);
					$last_dimension = $dimension;
				}
			}
		}

		return $this->selectable_dimensions;
	}

	// }}}
	// {{{ public static function getDateRange()

	public static function getDateRange($db, $where = null)
	{
		$date_range = SwatDB::queryRow($db,
			sprintf('select max(photo_date) as last_photo_date,
				min(photo_date) as first_photo_date
			from PinholePhoto where status = %s and %s',
			$db->quote(
				self::STATUS_PUBLISHED, 'integer'),
				($where === null) ? '1 = 1' : $where));

		if ($date_range === null)
			return null;
		else
			return array(new SwatDate($date_range->first_photo_date),
				new SwatDate($date_range->last_photo_date));
	}

	// }}}
	// {{{ public static function getStatusTitle()

	public static function getStatusTitle($status)
	{
		switch ($status) {
		case self::STATUS_PUBLISHED :
			$title = Pinhole::_('Published');
			break;

		case self::STATUS_UNPUBLISHED :
			$title = Pinhole::_('Hidden');
			break;

		case self::STATUS_PENDING :
			$title = Pinhole::_('Pending');
			break;

		default:
			$title = Pinhole::_('Unknown Photo Status');
			break;
		}

		return $title;
	}

	// }}}
	// {{{ public static function getStatuses()

	public static function getStatuses()
	{
		return array(
			self::STATUS_PUBLISHED =>
				self::getStatusTitle(self::STATUS_PUBLISHED),
			self::STATUS_UNPUBLISHED =>
				self::getStatusTitle(self::STATUS_UNPUBLISHED),
			self::STATUS_PENDING =>
				self::getStatusTitle(self::STATUS_PENDING),
		);
	}

	// }}}
	// {{{ protected function getImageSet()

	protected function getImageSet()
	{
		if ($this->image_set_shortname === null)
			throw new SwatException('To process images, an image type '.
				'shortname must be defined in the image dataobject.');

		$class_name = SwatDBClassMap::get('PinholeImageSet');
		$image_set = new $class_name();
		$image_set->setDatabase($this->db);
		$image_set->instance = $this->instance;
		$found = $image_set->loadByShortname($this->image_set_shortname);

		if (!$found)
			throw new SwatException(sprintf('Image set “%s” does not exist.',
				$this->image_set_shortname));

		return $image_set;
	}

	// }}}
	// {{{ protected function saveDimensionBinding()

	/**
	 * Saves an image dimension binding
	 *
	 * @param Imagick $imagick the imagick instance to work with.
	 * @param SiteImageDimension $dimension the image's dimension.
	 */
	protected function saveDimensionBinding(Imagick $imagick,
		SiteImageDimension $dimension)
	{
		$class_name = SwatDBClassMap::get('PinholePhotoDimensionBinding');
		$binding = new $class_name();
		$binding->setDatabase($this->db);
		$binding->photo = $this->id;
		$binding->dimension = $dimension->id;
		$binding->image_type = $dimension->default_type->id;
		$binding->width = $imagick->getImageWidth();
		$binding->height = $imagick->getImageHeight();
		$binding->save();

		$this->dimension_bindings->add($binding);
	}

	// }}}

	// save file
	// {{{ public static function saveUploadedFile()

	/**
	 * Saves a file that has been uploaded
	 *
	 * Saves photo files with unique filenames. If the file is an archive,
	 * the archive contents are extracted.
	 *
	 * @param string $name Name of the file input
	 * @return array $files An array in the form $file =>
	 *               $original_filename.
	 */
	public static function saveUploadedFile($name)
	{
		if (!isset($_FILES[$name]))
			throw new PinholeUploadException(
				'File input name not found.');

		$file = $_FILES[$name];

		$ext = strtolower(end(explode('.', $file['name'])));

		$filename = uniqid('file').'.'.$ext;
		$file_path = sprintf('%s/%s',
			sys_get_temp_dir(), $filename);

		move_uploaded_file($file['tmp_name'], $file_path);
		chmod($file_path, 0666);

		return self::parseFile($file_path, $file['name']);
	}

	// }}}
	// {{{ public static function parseFile()

	/**
	 * TODO: update documentation
	 */
	public static function parseFile($file, $original_filename = null)
	{
		if (!file_exists($file))
			throw new PinholeProcessingException(
				'File could not be found.');

		$finfo = finfo_open(FILEINFO_MIME);
		$mime_type = finfo_file($finfo, $file);

		if (in_array($mime_type, self::getArchiveMimeTypes()))
			$files = self::getArchivedFiles($file,
				array_search($mime_type, self::getArchiveMimeTypes()));
		else
			$files = array(basename($file) => $original_filename);

		return $files;
	}

	// }}}
	// {{{ protected static function getArchiveMimeTypes()

	/**
	 * Get an array of archive mime types
	 *
	 * @return array array of arhive mime types
	 */
	protected static function getArchiveMimeTypes()
	{
		return array(
			'zip'  => 'application/x-zip',
			'mac_zip'  => 'application/zip',
		);
	}

	// }}}
	// {{{ private static function getArchivedFiles()

	/**
	 * Processes an array of files
	 */
	private static function getArchivedFiles($file, $type)
	{
		$za = new ZipArchive();
		$opened = $za->open($file);

		if ($opened !== true)
			throw new PinholeProcessingException(
				'Error opening file archive');

		$files = array();
		$file_path = sys_get_temp_dir();

		for ($i = 0; $i < $za->numFiles; $i++) {
			$stat = $za->statIndex($i);
			$ext = strtolower(end(explode('.', $stat['name'])));

			// don't import files starting with '.' such as mac thumbnails
			$parts = explode('/', $stat['name']);
			foreach ($parts as $part)
				if (substr($part, 0, 1) == '.')
					continue 2;

			$filename = uniqid('file').'.'.$ext;
			$files[$filename] = self::normalizeArchiveFileFilename(
				$stat['name']);

			$za->renameIndex($i, $filename);
			$za->extractTo($file_path, $filename);
			chmod($file_path.'/'.$filename, 0666);
		}

		$za->close();

		unlink($file);

		return $files;
	}

	// }}}
	// {{{ private static function normalizeArchiveFileFilename()

	/**
	 * Normalizes filenames in ZIP archives to UTF-8 encoding
	 *
	 * The ZIP file specification specifies that filenames are encoded using
	 * IBM Code Page 437. In 2008, an addition to the specification was made to
	 * also allow UTF-8 encoded filenames. This method checks if the filename
	 * is UFT-8 and it not, converts from IBM Code Page 437 to UTF-8.
	 *
	 * @param string $filename
	 *
	 * @return string
	 */
	private static function normalizeArchiveFileFilename($filename)
	{
		// if not UTF-8, convert from IBM CP 437
		if (!SwatString::validateUtf8($filename)) {
			$filename = iconv('CP437', 'UTF-8', $filename);
		}

		return $filename;
	}

	// }}}

	// save meta data
	// {{{ public static function getMetaDataFromFile()

	/**
	 * Get the meta data from a photo
	 *
	 * @param string $filename
	 *
	 * @return array An array of PinholePhotoMetaDataBinding data objects
	 *               with $shortname as the key of the array.
	 */
	public static function getMetaDataFromFile($file)
	{
		$file = escapeshellarg($file);

		exec("exiftool -t $file", $tag_names);
		exec("exiftool -t -s $file", $values);

		$data_objects = array();

		for ($i = 0; $i < count($tag_names); $i++) {
			$ret = explode("\t", $values[$i]);
			if (!isset($ret[1]))
				continue;

			$meta_data = new PinholePhotoMetaDataBinding();
			$meta_data->shortname = strtolower($ret[0]);
			$meta_data->value = $ret[1];

			$ret = explode("\t", $tag_names[$i]);
			$meta_data->title = $ret[0];

			$data_objects[$meta_data->shortname] = $meta_data;
		}

		return $data_objects;
	}

	// }}}
	// {{{ protected function processInternal()

	/**
	 * Processes the image
	 *
	 * At this point in the process, the image already has a filename and id
	 * and is wrapped in a database transaction.
	 *
	 * @param string $image_file the image file to process
	 */
	protected function processInternal($image_file)
	{
		parent::processInternal($image_file);

		$meta_data = self::getMetaDataFromFile($image_file);
		$this->saveMetaData($meta_data);
	}

	// }}}
	// {{{ protected function saveMetaData()

	/**
	 * Get the meta data from a photo
	 *
	 * @param array An array of PinholePhotoMetaDataBinding data objects
	 *               with $shortname as the key of the array.
	 * @return array An array of PinholeMetaData dataobjects
	 */
	protected function saveMetaData($meta_data)
	{
		$instance_id = ($this->instance === null) ? null : $this->instance->id;

		$this->setPhotoDateByMetaData($meta_data);
		$this->setContentByMetaData($meta_data);

		$where_clause = sprintf('PinholeMetaData.instance %s %s',
			SwatDB::equalityOperator($instance_id),
			$this->db->quote($instance_id, 'integer'));

		$existing_meta_data = SwatDB::getOptionArray($this->db,
			'PinholeMetaData', 'shortname', 'id', null,
			$where_clause);

		foreach ($meta_data as $data) {
			$shortname = substr($data->shortname, 0, 255);
			$title = substr($data->title, 0, 255);

			if (mb_check_encoding($data->value, 'UTF-8')) {
				$value = $data->value;
			} else {
				// assume ISO-8859-1
				$value = iconv('ISO-8859-1', 'UTF-8', $data->value);
			}

			$value = substr($value, 0, 255);

			if (!in_array($shortname, $existing_meta_data)) {
				$meta_data_id = SwatDB::insertRow($this->db,
					'PinholeMetaData',
					array('text:shortname',
						'text:title',
						'integer:instance'),
					array('shortname' => $shortname,
						'title' => $title,
						'instance' => $instance_id),
					'id');
			} else {
				$meta_data_id = array_search($shortname,
					$existing_meta_data);
			}

			SwatDB::insertRow($this->db, 'PinholePhotoMetaDataBinding',
				array('integer:photo',
					'integer:meta_data',
					'text:value'),
				array('photo' => $this->id,
					'meta_data' => $meta_data_id,
					'value' => $value));
		}
	}

	// }}}

	// parse meta data
	// {{{ protected function setContentByMetaData()

	/**
	 * Set the photo title, description, and tags based on meta-data
	 */
	protected function setContentByMetaData($meta_data)
	{
		$this->setTitleByMetaData($meta_data);
		$this->setDescriptionByMetaData($meta_data);
		$this->setTagsByMetaData($meta_data);
	}

	// }}}
	// {{{ protected function setPhotoDateByMetaData()

	protected function setPhotoDateByMetaData($meta_data)
	{
		$date_fields = array('createdate', 'datetimeoriginal');
		foreach ($date_fields as $field) {
			if (isset($meta_data[$field])) {
				$photo_date = $this->parseMetaDataDate(
					$meta_data[$field]->value);

				if ($photo_date !== null && $photo_date->isPast()) {
					$this->photo_date = $photo_date;
					break;
				}
			}
		}
	}

	// }}}
	// {{{ protected function setTitleByMetaData()

	protected function setTitleByMetaData($meta_data)
	{
		$title_fields = array('object', 'headline');
		foreach ($title_fields as $field) {
			if (isset($meta_data[$field]) &&
					strlen($meta_data[$field]->value)) {

				$this->title = $meta_data[$field]->value;
				break;
			}
		}
	}

	// }}}
	// {{{ protected function setDescriptionByMetaData()

	protected function setDescriptionByMetaData($meta_data)
	{
		$description_fields = array('description', 'caption-abstract');
		foreach ($description_fields as $field) {
			if (isset($meta_data[$field]) &&
				strlen($meta_data[$field]->value)) {
				$this->description = $meta_data[$field]->value;
				break;
			}
		}
	}

	// }}}
	// {{{ protected function setTagsByMetaData()

	protected function setTagsByMetaData($meta_data)
	{
		$merged_tags = array();

		$tag_fields = array('city', 'location', 'sub-location');
		foreach ($tag_fields as $field) {
			if (isset($meta_data[$field]) &&
				strlen($meta_data[$field]->value)) {
				$merged_tags[] = $meta_data[$field]->value;
			}
		}

		// tags stored in lists
		$tag_fields = array('subject', 'keywords');
		foreach ($tag_fields as $field) {
			if (isset($meta_data[$field])) {
				$string = $meta_data[$field]->value;

				// use file wrapper hack because str_getcsv is only in
				// php >= 5.3.0
				$data = fopen('data://text/plain,'.$string, 'r');
				$tags = fgetcsv($data);

				$merged_tags = array_merge($merged_tags, $tags);
			}
		}

		if (count($merged_tags) > 0) {
			$added_tags = array();

			foreach ($merged_tags as $tag) {
				$tag_obj = $this->addMetaDataTag($tag);
				$added_tags[$tag_obj->name] = $tag_obj->title;
			}

			$this->addTagsByName($added_tags);
		}
	}

	// }}}
	// {{{ private function addMetaDataTag()

	private function addMetaDataTag($title)
	{
		$title = trim($title);

		// check to see if the tag already exists
		$instance_id = $this->instance->id;
		$sql = sprintf('select * from
			PinholeTag where lower(title) = lower(%1$s)
				and instance %2$s %3$s',
			$this->db->quote($title, 'text'),
			SwatDB::equalityOperator($instance_id),
			$this->db->quote($instance_id, 'integer'));

		$tags = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('PinholeTagDataObjectWrapper'));

		if (count($tags) > 0) {
			$tag_obj = $tags->getFirst();
		} else {
			$tag_obj = new PinholeTagDataObject();
			$tag_obj->setDatabase($this->db);
			$tag_obj->instance = $instance_id;
			$tag_obj->title = $title;
			$tag_obj->save();
		}

		return $tag_obj;
	}

	// }}}
	// {{{ private function parseMetaDataDate()

	private function parseMetaDataDate($date)
	{
		list($year, $month, $day, $hour, $minute, $second) =
			sscanf($date, "%d:%d:%d %d:%d:%d");

		$date = new SwatDate();
		$error = $date->setDayMonthYear($day, $month, $year);
		if (PEAR::isError($error))
			return null;

		$error = $date->setHourMinuteSecond($hour, $minute, $second);
		if (PEAR::isError($error))
			return null;

		return $date;
	}

	// }}}

	// loader methods
	// {{{ protected function loadDimensionBindings()

	/**
	 * Loads the dimension bindings for this image
	 *
	 * @return PinholePhotoDimensionBindingWrapper a recordset of dimension
	 *                                           bindings.
	 */
	protected function loadDimensionBindings()
	{
		$sql = 'select * from PinholePhotoDimensionBinding
				where PinholePhotoDimensionBinding.photo = %s';

		$sql = sprintf($sql,
			$this->db->quote($this->id, 'integer'));

		$wrapper = SwatDBClassMap::get('PinholePhotoDimensionBindingWrapper');
		return SwatDB::query($this->db, $sql, $wrapper);
	}

	// }}}
	// {{{ protected function loadTags()

	protected function loadTags()
	{
		// require statements are here to prevent circular dependency issues
		require_once 'Pinhole/tags/PinholeTag.php';
		require_once 'Pinhole/PinholeTagList.php';
		require_once 'Pinhole/dataobjects/PinholeTagDataObjectWrapper.php';

		$tag_list = null;

		$sql = sprintf('select * from PinholeTag where id in (
			select tag from PinholePhotoTagBinding where photo = %s)
				order by PinholeTag.title',
			$this->db->quote($this->id, 'integer'));

		$data_objects = SwatDB::query($this->db, $sql,
			'PinholeTagDataObjectWrapper');

		$tag_list = new PinholeTagList($this->db, $this->image_set->instance);
		foreach ($data_objects as $object)
			$tag_list->add(new PinholeTag($this->image_set->instance, $object));

		return $tag_list;
	}

	// }}}
	// {{{ protected function loadMetaData()

	protected function loadMetaData()
	{
		return PinholePhotoMetaDataBindingWrapper::loadSetFromDB(
			$this->db, $this->id);
	}

	// }}}
}

?>
