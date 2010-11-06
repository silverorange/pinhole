<?php

require_once 'Swat/SwatDate.php';
require_once 'Swat/exceptions/SwatException.php';
require_once 'Site/dataobjects/SiteImage.php';
require_once 'Site/SiteCommentable.php';

require_once 'Pinhole/dataobjects/PinholeComment.php';
require_once 'Pinhole/dataobjects/PinholeCommentWrapper.php';
require_once 'Pinhole/dataobjects/PinholeImageSet.php';
require_once 'Pinhole/dataobjects/PinholePhotoUploadSet.php';
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
 * @copyright 2007-2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhoto extends SiteImage implements SiteCommentable
{
	// {{{ constants

	/**
	 * Not yet processed
	 */
	const STATUS_UNPROCESSED  = 3;

	/**
	 * Error occured while processing
	 */
	const STATUS_PROCESSING_ERROR = 5;

	/**
	 * Processed, but awaiting content editing
	 */
	const STATUS_PENDING      = 0;

	/**
	 * Published and displayed on the front-end
	 */
	const STATUS_PUBLISHED    = 1;

	/**
	 * Published but hidden on the front-end
	 */
	const STATUS_UNPUBLISHED  = 2;

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
	 * @var SwatDate
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
	 * @var SwatDate
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
	 * @var SwatDate
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
	 * The status of comments on this photo.
	 *
	 * @var integer
	 */
	public $comment_status;

	/**
	 * Time-zone of the photo
	 *
	 * @var string
	 */
	 public $photo_time_zone;

	/**
	 * Time-zone of the camera
	 *
	 * If the camera time zone and the photo time zone are not the same,
	 * the date of the photo will be converted to the camera time zone
	 * when processed.
	 *
	 * @var string
	 */
	 public $camera_time_zone;

	/**
	 * Private
	 *
	 * @var boolean
	 */
	 public $private;

	/**
	 * Auto-publish
	 *
	 * @var boolean
	 */
	 public $auto_publish;

	/**
	 * Set content by meta-data when processing
	 *
	 * @var boolean
	 */
	 public $set_content_by_meta_data;

	/**
	 * Set tags by meta-data when processing
	 *
	 * @var boolean
	 */
	 public $set_tags_by_meta_data;

	/**
	 * Auto-rotate when processing
	 *
	 * @var boolean
	 */
	 public $auto_rotate = true;

	/**
	 * For sale
	 *
	 * @var boolean
	 */
	 public $for_sale;

	/**
	 * GPS Latitude
	 *
	 * @var float
	 */
	public $gps_latitude;

	/**
	 * GPS Longitude
	 *
	 * @var float
	 */
	public $gps_longitude;

	/**
	 * DAV Uploaded
	 *
	 * @var boolean
	 */
	public $dav_upload;

	// }}}
	// {{{ protected properties

	protected $selectable_dimensions;

	/**
	 * @var resource
	 */
	protected static $finfo = false;

	// }}}

	// dataobject methods
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

		$this->registerInternalProperty('upload_set',
			SwatDBClassMap::get('PinholePhotoUploadSet'));

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

	// comment methods
	// {{{ public static function getCommentStatusTitle()

	public static function getCommentStatusTitle($status)
	{
		switch ($status) {
		case SiteCommentStatus::OPEN :
			$title = Pinhole::_('Open');
			break;

		case SiteCommentStatus::LOCKED :
			$title = Pinhole::_('Locked');
			break;

		case SiteCommentStatus::MODERATED :
			$title = Pinhole::_('Moderated');
			break;

		case SiteCommentStatus::CLOSED :
			$title = Pinhole::_('Closed');
			break;

		default:
			$title = Pinhole::_('Unknown Comment Status');
			break;
		}

		return $title;
	}

	// }}}
	// {{{ public static function getCommentStatuses()

	public static function getCommentStatuses()
	{
		return array(
			SiteCommentStatus::OPEN =>
				self::getCommentStatusTitle(SiteCommentStatus::OPEN),
			SiteCommentStatus::MODERATED =>
				self::getCommentStatusTitle(SiteCommentStatus::MODERATED),
			SiteCommentStatus::LOCKED =>
				self::getCommentStatusTitle(SiteCommentStatus::LOCKED),
			SiteCommentStatus::CLOSED =>
				self::getCommentStatusTitle(SiteCommentStatus::CLOSED),
		);
	}

	// }}}
	// {{{ public function getCommentCount()

	public function getCommentCount()
	{
		if ($this->hasInternalValue('comment_count') &&
			$this->getInternalValue('comment_count') !== null) {
			$comment_count = $this->getInternalValue('comment_count');
		} else {
			$this->checkDB();

			$sql = sprintf('select comment_count
				from PinholePhotoCommentCountView
				where photo = %s',
				$this->db->quote($this->id, 'integer'));

			$comment_count = SwatDB::queryOne($this->db, $sql);
		}

		return $comment_count;
	}

	// }}}
	// {{{ public function getVisibleComments()

	/**
	 * Note: The results of this method are intentionally not cached or
	 * serialized. Because the comment object serializes its photo reference,
	 * serializing the visible comments results in at best oversized serialized
	 * data structures (to the point of crashing PHP) and at worst infinite
	 * recursion upon serialization.
	 */
	public function getVisibleComments($limit = null, $offset = 0)
	{
		$this->checkDB();

		$sql = sprintf('select * from PinholeComment
			where photo = %s and status = %s and spam = %s
			order by createdate',
			$this->db->quote($this->id, 'integer'),
			$this->db->quote(SiteComment::STATUS_PUBLISHED, 'integer'),
			$this->db->quote(false, 'boolean'));

		$wrapper = SwatDBClassMap::get('PinholeCommentWrapper');

		if ($limit !== null) {
			$this->db->setLimit($limit, $offset);
		}

		$comments = SwatDB::query($this->db, $sql, $wrapper);

		// set photo on comment objects so they don't have to query it again
		foreach ($comments as $comment) {
			$comment->photo = $this;
		}

		return $comments;
	}

	// }}}
	// {{{ public function getVisibleCommentCount()

	public function getVisibleCommentCount()
	{
		if ($this->hasInternalValue('visible_comment_count') &&
			$this->getInternalValue('visible_comment_count') !== null) {
			$comment_count = $this->getInternalValue('visible_comment_count');
		} else {
			$this->checkDB();

			$sql = sprintf('select visible_comment_count
				from PinholePhotoVisibleCommentCountView
				where photo = %s',
				$this->db->quote($this->id, 'integer'));

			$comment_count = SwatDB::queryOne($this->db, $sql);
		}

		return $comment_count;
	}

	// }}}
	// {{{ public function hasVisibleCommentStatus()

	public function hasVisibleCommentStatus()
	{
		return ($this->comment_status == SiteCommentStatus::OPEN ||
			$this->comment_status == SiteCommentStatus::MODERATED ||
			($this->comment_status == SiteCommentStatus::LOCKED &&
			$this->getVisibleCommentCount() > 0));
	}

	// }}}
	// {{{ public function getCommentStatus()

	/**
	 * Part of the {@link SiteCommentStatus} interface
	 *
	 * @return integer the comment status of this photo.
	 */
	public function getCommentStatus()
	{
		return $this->comment_status;
	}

	// }}}
	// {{{ public function addComment()

	/**
	 * Adds a comment to this photo
	 *
	 * Part of the {@link SiteCommentable} interface.
	 *
	 * @param SiteComment $comment the comment to add.
	 */
	public function addComment(SiteComment $comment)
	{
		$this->comments->add($comment);
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
		if ($set_publish_date) {
			$this->publish_date = new SwatDate();
			$this->publish_date->toUTC();
		}

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

	public function getFileDirectory($shortname)
	{
		$dimension = $this->image_set->getDimensionByShortname(
			$shortname);

		return sprintf('%s/%s/%s',
			$this->getFileBase(),
			($dimension->publicly_accessible) ? 'public' : 'private',
			$dimension->shortname);
	}

	// }}}
	// {{{ public function getTitle()

	/**
	 * Gets the title of this photo
	 *
	 * Part of the {@link SiteCommentable} interface.
	 *
	 * @param boolean $show_filename optional. Whether to show the photo's
	 *                                filename if no title is set. Defaults to
	 *                                false.
	 *
	 * @return string the title of this photo.
	 */
	public function getTitle($show_filename = false)
	{
		$title = $this->title;

		if ($this->title === null && $show_filename) {
			$title = $this->original_filename;
		}

		return $title;
	}

	// }}}
	// {{{ public function addTagsByName()

	public function addTagsByName(array $tag_names,
		$clear_existing_tags = false)
	{
		$this->checkDB();

		$instance_id = ($this->image_set->instance === null) ? null :
			$this->image_set->instance->id;

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
	// {{{ public function isProcessed()

	/**
	 * Whether or not this photo has been processed
	 *
	 * @return boolean
	 */
	public function isProcessed()
	{
		return ($this->status !== self::STATUS_UNPROCESSED);
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
	// {{{ public function getTagsAsTagList()

	public function getTagsAsTagList(SiteApplication $app)
	{
		// require statements are here to prevent circular dependency issues
		require_once 'Pinhole/tags/PinholeTag.php';
		require_once 'Pinhole/PinholeTagList.php';

		$tag_list = new PinholeTagList($this->app);
		foreach ($this->tags as $tag)
			$tag_list->add(new PinholeTag($this->image_set->instance, $tag));

		return $tag_list;
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
		$binding->width      = $imagick->getImageWidth();
		$binding->height     = $imagick->getImageHeight();
		$binding->photo      = $this->id;
		$binding->dimension  = $dimension->id;
		$binding->filesize  = $imagick->getImageSize();
		$binding->image_type =
			$this->getDimensionImageType($imagick, $dimension);

		$resolution = $imagick->getImageResolution();
		$binding->dpi  = intval($resolution['x']);

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
	public static function parseFile($file_path, $original_filename = null)
	{
		if (!file_exists($file_path))
			throw new PinholeProcessingException(
				'File could not be found.');

		if (($archive_type = self::getArchiveType($file_path)) === false) {
			$files = array(basename($file_path) => $original_filename);
		} else {
			$files = self::getArchivedFiles($file_path, $archive_type);
		}

		return $files;
	}

	// }}}
	// {{{ protected function getNewImagick()

	/**
	 * Gets a new Imagick instance from a file
	 *
	 * @param string $image_file the image file to process.
	 * @param SiteImageDimension $dimension the dimension to process.
	 */
	protected function getNewImagick($image_file,
		SiteImageDimension $dimension)
	{
		$imagick = parent::getNewImagick($image_file, $dimension);

		if ($this->auto_rotate) {
			self::autoRotateImage($image_file, $imagick);
		}

		return $imagick;
	}

	// }}}
	// {{{ protected function fitToDimension()

	/**
	 * Resizes an image to fit in a given dimension
	 *
	 * @param Imagick $imagick the imagick instance to work with.
	 * @param SiteImageDimension $dimension the dimension to process.
	 */
	protected function fitToDimension(Imagick $imagick,
		SiteImageDimension $dimension)
	{
		$max_width = $dimension->max_width;
		$max_height = $dimension->max_height;

		if ($dimension->shortname == 'large') {
			// handle panoramas
			$width = $imagick->getImageWidth();
			$height = $imagick->getImageHeight();

			if (($width / $height) > 3) {
				$dimension->max_width = null;
			} elseif (($height / $width) > 3) {
				$dimension->max_height = null;
			}
		}

		parent::fitToDimension($imagick, $dimension);

		$dimension->max_width = $max_width;
		$dimension->max_height = $max_height;
	}

	// }}}
	// {{{ protected static function autoRotateImage()

	protected static function autoRotateImage($image_file, Imagick $imagick)
	{
		$orientation = exec(sprintf('exiftool -Orientation -n -S -t %s',
			escapeshellarg($image_file)));

		if (!ctype_digit($orientation))
			return;
		else
			$orientation = intval($orientation);

		$rotated = true;

		switch($orientation) {
		case Imagick::ORIENTATION_TOPRIGHT:
			// Mirror horizontal
			$imagick->flopImage();
			break;
		case Imagick::ORIENTATION_BOTTOMRIGHT:
			// Rotate 180
			$imagick->rotateImage(new ImagickPixel(), 180);
			break;
		case Imagick::ORIENTATION_BOTTOMLEFT:
			// Mirror vertical
			$imagick->flipImage();
			break;
		case Imagick::ORIENTATION_LEFTTOP:
			// Mirror horizontal and rotate 270 CW
			$imagick->transverseImage();
			break;
		case Imagick::ORIENTATION_RIGHTTOP:
			// Rotate 90 CW
			$imagick->rotateImage(new ImagickPixel(), 90);
			break;
		case Imagick::ORIENTATION_RIGHTBOTTOM:
			// Mirror horizontal and rotate 90 CW
			$imagick->transposeImage();
			break;
		case Imagick::ORIENTATION_LEFTBOTTOM:
			// Rotate 270 CW
			$imagick->rotateImage(new ImagickPixel(), 270);
			break;
		default:
			$rotated = false;
		}

		return $rotated;
	}

	// }}}

	// zip archive handling
	// {{{ protected static function getArchivedFiles()

	/**
	 * Processes an array of files
	 */
	protected static function getArchivedFiles($file_path, $type)
	{
		$za = new ZipArchive();

		if ($za->open($file_path) !== true) {
			throw new SwatException(sprintf('Error opening file archive “%s”',
				$file_path));
		}

		$dir = dirname($file_path);

		$files = array();

		for ($i = 0; $i < $za->numFiles; $i++) {
			$stat = $za->statIndex($i);

			$original_filename = self::normalizeArchiveFileFilename(
				$stat['name']);

			// ignore hidden files
			if (preg_match('@(^\.|/\.)@', $original_filename) === 1) {
				continue;
			}

			// ignore directories
			if (preg_match('@/$@', $original_filename) === 1) {
				continue;
			}

			// build temp filename
			$filename = self::getTempFilename($original_filename);

			// ignore certain common extensions
			if (strpos($filename, '.') !== false) {
				$ext = end(explode('.', $filename));
				$ignore_extensions = array(
					'db',      // e.g. thumbs.db
					'exe',     // e.g. gallery.exe
					'torrent',
				);
				if (in_array($ext, $ignore_extensions)) {
					continue;
				}
			}

			$files[$filename] = $original_filename;

			// extract the file to the queue directory
			$za->renameIndex($i, $filename);
			$za->extractTo($dir, $filename);

			// set file permissions
			chmod($dir.'/'.$filename, 0664);

			// recurse into contained archives
			$archive_path = $dir.'/'.$filename;
			$archive_type = self::getArchiveType($archive_path);
			if ($archive_type !== false) {
				$files = array_merge(
					$files,
					self::getArchivedFiles($archive_path, $archive_type)
				);

				// do not include the recursed archive in results
				unset($files[$filename]);
			}
		}

		$za->close();

		// remove the zip file
		unlink($file_path);

		return $files;
	}

	// }}}
	// {{{ protected static function normalizeArchiveFileFilename()

	/**
	 * Normalizes filenames in ZIP archives to UTF-8 encoding
	 *
	 * The ZIP file specification specifies that filenames are encoded using
	 * IBM Code Page 437. In 2008, an addition to the specification was made to
	 * also allow UTF-8 encoded filenames. This method checks if the filename
	 * is UTF-8 and if not, converts from IBM Code Page 437 to UTF-8.
	 *
	 * @param string $filename
	 *
	 * @return string
	 */
	protected static function normalizeArchiveFileFilename($filename)
	{
		// if not UTF-8, convert from IBM CP 437
		if (!SwatString::validateUtf8($filename)) {
			$filename = iconv('CP437', 'UTF-8', $filename);
		}

		return $filename;
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
			'zip'      => 'application/x-zip',
			'mac_zip'  => 'application/zip',
		);
	}

	// }}}
	// {{{ protected static function getArchiveType()

	protected static function getArchiveType($file_path)
	{
		$type = false;

		if (extension_loaded('fileinfo')) {
			// Use the fileinfo extension if available.

			// PHP >= 5.3.0 supports returning only the mimetype
			// without returning the encoding. See
			// http://us3.php.net/manual/en/fileinfo.constants.php for
			// details.
			$mime_constant = (defined('FILEINFO_MIME_TYPE')) ?
				FILEINFO_MIME_TYPE : FILEINFO_MIME;

			$finfo = new finfo($mime_constant);
			$mime_type = reset(explode(';', $finfo->file($file_path)));

		} elseif (function_exists('mime_content_type')) {
			// Fall back to mime_content_type() if available.
			$mime_type = mime_content_type($file_path);
		}

		$types = self::getArchiveMimeTypes();
		$type = array_search($mime_type, $types);
		var_dump($type); echo '<hr>';
		return $type;
	}

	// }}}
	// {{{ protected static function getTempFilename()

	protected static function getTempFilename($original_filename)
	{
		// build temp filename
		$filename = str_replace('.', '-', uniqid('file', true));

		// get extension
		if (strpos($original_filename, '.') !== false) {
			$ext = strtolower(end(explode('.', $original_filename)));
			$filename.= '.'.$ext;
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
			$meta_data->value = self::normalizeMetaDataValue(
				$meta_data->shortname, $ret[1]);

			$ret = explode("\t", $tag_names[$i]);
			$meta_data->title = $ret[0];

			$data_objects[$meta_data->shortname] = $meta_data;
		}

		return $data_objects;
	}

	// }}}
	// {{{ protected static function normalizeMetaDataValue()

	protected static function normalizeMetaDataValue($name, $value)
	{
		switch ($name) {
		case 'exposuretime' :
		case 'exposurecompensation' :
			// matches any fraction:
				// 0.004 s (1/274)
				// 1073742/1073741824
				// -1/4
			if (preg_match('/(?<numerator>[\d-]+)\/(?<denominator>\d+)/',
				$value, $regs)) {

				return (((float) $regs['numerator'] /
					(float) $regs['denominator']));
			}
		case 'focallength' :
			if (strpos($value, 'mm')) {
				$value = trim(str_replace('mm', '', $value));
			}

			$value = round($value, 1);
			if (intval($value) == $value)
				$value = intval($value);

			return $value;
		default:
			return $value;
		}
	}

	// }}}
	// {{{ protected function prepareForProcessing()

	/**
	 * Processes the image
	 *
	 * At this point in the process, the image already has a filename and id
	 * and is wrapped in a database transaction.
	 *
	 * @param string $image_file the image file to process
	 */
	protected function prepareForProcessing($image_file)
	{
		parent::prepareForProcessing($image_file);

		$meta_data = self::getMetaDataFromFile($image_file);
		$this->saveMetaDataInternal($meta_data);
		$this->setContentByMetaData($meta_data);
	}

	// }}}
	// {{{ protected function saveMetaDataInternal()

	/**
	 * Get the meta data from a photo
	 *
	 * @param array An array of PinholePhotoMetaDataBinding data objects
	 *               with $shortname as the key of the array.
	 * @return array An array of PinholeMetaData dataobjects
	 */
	protected function saveMetaDataInternal($meta_data)
	{
		$instance_id = ($this->image_set->instance === null) ? null :
			$this->image_set->instance->id;

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
	// {{{ protected function setContentByMetaData()

	protected function setContentByMetaData($meta_data)
	{
		$this->setPhotoDateByMetaData($meta_data);
		$this->setGpsCoordinatesByMetaData($meta_data);

		if ($this->set_content_by_meta_data) {
			$this->setTitleByMetaData($meta_data);
			$this->setDescriptionByMetaData($meta_data);
		}

		if ($this->set_tags_by_meta_data) {
			$this->setTagsByMetaData($meta_data);
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

		$tag_fields = array('city', 'location', 'sub-location', 'country',
			'country-primarylocationname');

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
	// {{{ protected function setPhotoDateByMetaData()

	protected function setPhotoDateByMetaData($meta_data)
	{
		$now = new SwatDate();
		$date_fields = array('createdate', 'datetimeoriginal');
		foreach ($date_fields as $field) {
			if (isset($meta_data[$field])) {
				$photo_date = $this->parseMetaDataDate(
					$meta_data[$field]->value);

				if ($photo_date !== null && $photo_date->before($now)) {
					$this->photo_date = $photo_date;
					break;
				}
			}
		}

		if ($this->photo_date !== null) {
			if ($this->camera_time_zone !== null)
				$this->photo_date->setTZById($this->camera_time_zone);

			$this->photo_date->toUTC();
		}
	}

	// }}}
	// {{{ protected function setGpsCoordinatesByMetaData()

	protected function setGpsCoordinatesByMetaData($meta_data)
	{
		$fields = array('gpslatitude', 'gpslongitude');
		foreach ($fields as $field) {
			if (isset($meta_data[$field])) {
				$name = ($field == 'gpslatitude') ?
					'gps_latitude' : 'gps_longitude';

				$this->$name = $this->parseMetaDataGps(
					$meta_data[$field]->value);
			}
		}
	}

	// }}}
	// {{{ private function addMetaDataTag()

	private function addMetaDataTag($title)
	{
		$title = trim($title);

		// check to see if the tag already exists
		$instance_id = ($this->image_set->instance === null) ? null :
			$this->image_set->instance->id;

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
			$tag_obj->setInstance($this->image_set->instance);
			$tag_obj->title = $title;
			$tag_obj->save();
		}

		return $tag_obj;
	}

	// }}}
	// {{{ private function parseMetaDataDate()

	private function parseMetaDataDate($date_string)
	{
		list($year, $month, $day, $hour, $minute, $second) =
			sscanf($date_string, "%d:%d:%d %d:%d:%d");

		$date = new SwatDate();
		$error = $date->setDate($year, $month, $day);
		if (PEAR::isError($error))
			return null;

		$error = $date->setTime($hour, $minute, $second);
		if (PEAR::isError($error))
			return null;

		$date->toUTC();

		return $date;
	}

	// }}}
	// {{{ private function parseMetaDataGps()

	private function parseMetaDataGps($gps_string)
	{
		$float = null;

		// 11 deg 15' 18.00" E
		$match = preg_match('/^([\d]+) deg ([\d]+)\' ([\d\.]+)" ([NWSE])/',
			$gps_string, $regs);

		if ($match) {
			list($s, $degrees, $minutes, $seconds, $hemisphere) = $regs;

			$float = $degrees + ((float) $minutes / 60) +
				((float) $seconds / 3600);

			if ($hemisphere == 'S' || $hemisphere == 'W')
				$float = $float * -1;
		}

		return $float;
	}

	// }}}

	// loader methods
	// {{{ protected function loadComments()

	/**
	 * Loads comments for this photo, this never includes spam
	 *
	 * @return SiteCommentWrapper
	 */
	protected function loadComments()
	{
		$sql = 'select PinholeComment.*
			from PinholeComment
			where PinholeComment.photo = %s and spam = %s
			order by createdate';

		$sql = sprintf($sql,
			$this->db->quote($this->id, 'integer'),
			$this->db->quote(false, 'boolean'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('PinholeCommentWrapper'));
	}

	// }}}
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
		// require statement is here to prevent circular dependency issues
		require_once 'Pinhole/dataobjects/PinholeTagDataObjectWrapper.php';

		$sql = sprintf('select * from PinholeTag where id in (
			select tag from PinholePhotoTagBinding where photo = %s)
				order by PinholeTag.title',
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('PinholeTagDataObjectWrapper'));
	}

	// }}}
	// {{{ protected function loadMetaData()

	protected function loadMetaData()
	{
		return PinholePhotoMetaDataBindingWrapper::loadSetFromDB(
			$this->db, $this->id);
	}

	// }}}

	// saver methods
	// {{{ protected function saveComments()

	/**
	 * Automatically saves comments on this photo when this photo is saved
	 */
	protected function saveComments()
	{
		foreach ($this->comments as $comment)
			$comment->photo = $this;

		$this->comments->setDatabase($this->db);
		$this->comments->save();
	}

	// }}}
}

?>
