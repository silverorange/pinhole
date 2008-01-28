<?php

require_once 'Swat/SwatDate.php';
require_once 'Swat/exceptions/SwatException.php';
require_once 'Site/dataobjects/SiteImage.php';
require_once 'Pinhole/dataobjects/PinholeImageSet.php';
require_once 'Pinhole/dataobjects/PinholePhotoDimensionBindingWrapper.php';
require_once 'Pinhole/dataobjects/PinholePhotoMetaDataBindingWrapper.php';

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
	 * STATUS_PENDING - uploaded but not yet added to the site
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

	// }}}
	// {{{ private properties

	/**
	 * The instance for this photo - only used for processing.
	 *
	 * @var SiteInstance
	 */
	private $instance;

	// }}}
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
				$this->getFilename($dimension));

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
			$this->getFilename($dimension));
	}

	// }}}
	// {{{ public function getTitle()

	/**
	 * Gets the title of this photo
	 *
	 * @return string the title of this photo. If this photo has no title then
	 *                 the original filename is returned.
	 */
	public function getTitle()
	{
		$title = $this->title;

		if ($this->title === null)
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
	// {{{ public static function setStatus()

	public function setStatus($status)
	{
		if (!array_key_exists($status, self::getStatuses()))
			throw new SwatException('Invalid Status');	

		if ($status == self::STATUS_PUBLISHED &&
			$this->status != self::STATUS_PUBLISHED)
			$this->publish_date = new SwatDate();

		$this->status = $status;
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
			select tag from PinholePhotoTagBinding where photo = %s)',
			$this->db->quote($this->id, 'integer'));

		$data_objects = SwatDB::query($this->db, $sql,
			'PinholeTagDataObjectWrapper');

		$tag_list = new PinholeTagList($this->db, $this->image_set->instance);
		foreach ($data_objects as $object)
			$tag_list->add(new PinholeTag($object));

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
