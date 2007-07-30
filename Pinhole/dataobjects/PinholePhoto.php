<?php

require_once 'Pinhole/dataobjects/PinholeDimensionWrapper.php';
require_once 'Pinhole/dataobjects/PinholePhotoDimensionBindingWrapper.php';
require_once 'Pinhole/dataobjects/PinholePhotoMetaDataBindingWrapper.php';
require_once 'Swat/SwatDate.php';
require_once 'Swat/exceptions/SwatException.php';
require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A dataobject class for photos
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhoto extends SwatDBDataObject
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
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * The PinholeInstance this photo belongs to
	 *
	 * @var PinholeInstance
	 */
	public $instance;

	/**
	 * User visible title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Photo description
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Upload date
	 *
	 * The date the photo was uploaded
	 *
	 * @var Date
	 */
	public $upload_date;

	/**
	 * Filename
	 *
	 * A unique filename stored without an extension.
	 *
	 * @var string
	 */
	public $filename;

	/**
	 * Original filename
	 *
	 * @var string
	 */
	public $original_filename;

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

	// }}}
	// {{{ private properties

	private $dimensions = array();

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
	// {{{ public function setDimension()

	public function setDimension($shortname,
		PinholePhotoDimensionBinding $dimension)
	{
		$dimension->photo = $this;
		$this->dimensions[$shortname] = $dimension;
	}

	// }}}
	// {{{ public function getDimension()

	public function getDimension($shortname)
	{
		if (isset($this->dimensions[$shortname]))
			return $this->dimensions[$shortname];

		$sql = sprintf('select PinholePhotoDimensionBinding.*
			from PinholePhotoDimensionBinding
			inner join PinholeDimension on
				PinholePhotoDimensionBinding.dimension = PinholeDimension.id
			where PinholePhotoDimensionBinding.photo = %s
				and PinholeDimension.shortname = %s',
			$this->db->quote($this->id, 'integer'),
			$this->db->quote($shortname, 'text'));

		$dimension = SwatDB::query($this->db, $sql,
			'PinholePhotoDimensionBindingWrapper');

		if ($dimension->getCount() > 0) {
			$this->setDimension($shortname, $dimension->getFirst());
			return $dimension->getFirst();
		}

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
		if ($this->title === null)
			return $this->original_filename;
		else
			return $this->title;
	}

	// }}}
	// {{{ static public function setStatus()

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
	// {{{ static public function getDateRange()

	static public function getDateRange($db, $where = null)
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
	// {{{ static public function getStatusTitle()

	static public function getStatusTitle($status)
	{
		switch ($status) {
		case self::STATUS_PUBLISHED :
			return Pinhole::_('Published');
		case self::STATUS_UNPUBLISHED :
			return Pinhole::_('Hidden');
		case self::STATUS_PENDING :
			return Pinhole::_('Pending');
		}
	}

	// }}}
	// {{{ static public function getStatuses()

	static public function getStatuses()
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
		$this->table = 'PinholePhoto';
		$this->id_field = 'integer:id';

		$this->registerInternalProperty('photographer',
			SwatDBClassMap::get('PinholePhotographer'));

		$this->registerDateProperty('upload_date');
		$this->registerDateProperty('publish_date');
		$this->registerDateProperty('photo_date');
	}

	// }}}
	// {{{ protected function getFilename()

	protected function getFilename()
	{
		if ($this->filename === null)
			throw new SwatException('Filename must be set
				on the dataobject');

		return $this->filename;
	}

	// }}}

	// loader methods
	// {{{ protected function loadTags()

	protected function loadTags()
	{
		// require statements are here to prevent circular dependency issues
		require_once 'Pinhole/tags/PinholeTag.php';
		require_once 'Pinhole/PinholeTagList.php';
		require_once 'Pinhole/dataobjects/PinholeTagDataObjectWrapper.php';

		$sql = sprintf('select * from PinholeTag where id in (
			select tag from PinholePhotoTagBinding where photo = %s)',
			$this->db->quote($this->id, 'integer'));

		$data_objects = SwatDB::query($this->db, $sql,
			'PinholeTagDataObjectWrapper');
		
		$tag_list = new PinholeTagList($this->db);
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
	// {{{ protected function loadDimensions()

	protected function loadDimensions()
	{
		$sql = sprintf('select PinholePhotoDimensionBinding.*,
				PinholeDimension.width, PinholeDimension.height,
				PinholeDimension.publicly_accessible
				from PinholePhotoDimensionBinding
				inner join PinholeDimension on
					PinholeDimension.id = PinholePhotoDimensionBinding.dimension
				where PinholePhotoDimensionBinding.photo = %s)',
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			'PinholeDimensionBindingWrapper');
	}

	// }}}

	// database loading and saving
	// {{{ protected function deleteInternal()

	/**
	 * Deletes this object from the database
	 */
	protected function deleteInternal()
	{
		foreach ($this->dimensions as $dimension)
			if (file_exists($dimension->getPath()))
				unlink($dimension->getPath());

		parent::deleteInternal();
	}

	// }}}
}

?>
