<?php

//require_once 'Pinhole/dataobjects/PinholeTagWrapper.php';
require_once 'Pinhole/dataobjects/PinholeDimensionWrapper.php';
require_once 'Pinhole/dataobjects/PinholePhotoDimensionBindingWrapper.php';
require_once 'Swat/SwatDate.php';
require_once 'Swat/exceptions/SwatFileNotFoundException.php';
require_once 'Swat/exceptions/SwatException.php';
require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Image/Transform.php';

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

	const STATUS_PENDING = 0;
	const STATUS_PUBLISHED = 1;
	const STATUS_UNPUBLISHED = 2;

	const DATE_PART_YEAR = 1;
	const DATE_PART_MONTH = 2;
	const DATE_PART_DAY = 4;
	const DATE_PART_TIME = 8;

	// }}}
	// {{{ public properties

	/**
	 * 
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * 
	 *
	 * @var string
	 */
	public $title;

	/**
	 * 
	 *
	 * @var string
	 */
	public $description;

	/**
	 * 
	 *
	 * @var Date
	 */
	public $upload_date;

	/**
	 * 
	 *
	 * @var string
	 */
	public $filename;

	/**
	 * 
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
	 * references PinholePhotographer(id),
	 *
	 * @var integer
	 */
	public $photographer;

	/**
	 * 
	 *
	 * @var Date
	 */
	public $photo_date;

	/**
	 * not null default 0,
	 *
	 * @var integer
	 */
	public $photo_date_parts;

	/**
	 * 
	 *
	 * @var Date
	 */
	public $publish_date;

	/**
	 * Meta Data
	 *
	 * An array of PinholeMetaData dataobjects
	 *
	 * @array
	 */
	public $meta_data;

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
	// {{{ public function getCompressionQuality()

	// TODO: move this to Dimension object
	public function getCompressionQuality()
	{
		return 85;
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

		$this->registerDateProperty('upload_date');
		$this->registerDateProperty('publish_date');
		$this->registerDateProperty('photo_date');
	}

	// }}}

	// {{{ protected function getDpi()

	protected function getDpi()
	{
		return 72;
	}

	// }}}
	// {{{ protected function getFilename()

	protected function getFilename()
	{
		if ($this->id === null && $this->filename === null)
			$this->filename = sha1(uniqid(rand(), true));
		elseif ($this->filename === null)
			throw new SwatException('Filename must be set
				on the dataobject');

		return $this->filename;
	}

	// }}}

	// processing methods
	// {{{ public function createFromFile()

	public function createFromFile($file, $original_filename)
	{
		static $dimensions;

		if (!file_exists($file))
			throw new SwatFileNotFoundException(null, 0, $file);

		$filename = $this->getFilename();

		$this->original_filename = $original_filename;
		$this->upload_date = new SwatDate();
		//$this->meta_data = $this->getMetaDataFromFile($file);
		$this->serialized_exif = serialize(exif_read_data($file));

		if ($dimensions === null)
			$dimensions = SwatDB::query($this->db,
				'select * from PinholeDimension',
				'PinholeDimensionWrapper');

		$transformer = Image_Transform::factory('Imagick2');
		if (PEAR::isError($transformer))
			throw new AdminException($transformer);

		$transformer->load($file);

		foreach ($dimensions as $dimension) {
			$transformed = $this->processImage($transformer, $dimension);

			$dimension_binding = new PinholeDimensionBinding();
			$dimension_binding->photo = $this;
			$dimension_binding->dimension = $dimension;
			$dimension_binding->width = $transformed->new_x;
			$dimension_binding->height = $transformed->new_y;
			$dimension_binding->save();

			$transformed->save($dimension_binding->getPath(),
				false, $this->getCompressionQuality());
		}
	}

	// }}}
	// {{{ public function processImage()

	/**
	 * Does resizing for images
	 *
	 * @param Image_Transform $transformer the image transformer to work with.
	 *                                     The tranformer should already have
	 *                                     an image loaded.
	 *
	 * @param PinholeDimension $dimension the dimension to create.
	 *
	 * @return Image_Transform $transformer the image transformer with the
	 * 			 		processed image.
	 *
	 * @throws SwatException if no image is loaded in the transformer.
	 */
	public function processImage(Image_Transform $transformer,
		PinholeDimension $dimension)
	{
		if ($transformer->image === null)
			throw new SwatException('No image loaded.');

		// TODO: This doesn't handle panoramas corrently right now

		if ($dimension->max_height !== null &&
			$dimension->max_width !== null &&
			$dimension->crop_to_max)
			$this->cropToMax($transformer, $dimension);
		else
			$this->fitToMax($transformer, $dimension);

		$dpi = $this->getDpi();
		$transformer->setDpi($dpi, $dpi);

		if ($dimension->strip)
			$transformer->strip();

		return $transformer;
	}

	// }}}
	// {{{ public function publish()

	/**
	 * Publish photo to the site
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
	// {{{ public function getTitle()

	/*
	 * Get a readable title for a photo
	 *
	 * @return string a readable title for a photo 
	 */
	public function getTitle()
	{
		if ($this->title === null)
			return $this->original_filename;
		else
			return $this->title;
	}

	// }}}
	// {{{ protected function getMetaDataFromFile()

	/**
	 * Get the meta data from a photo
	 *
	 * @param string $filename 
	 * @return array An array of PinholeMetaData dataobjects 
	 */
	protected function getMetaDataFromFile($filename)
	{
		exec("exiftool -t $filename", $tag_names);
		exec("exiftool -t -s $filename", $values);

		$meta_data = SwatDB::queryColumn($this->db,
			'PinholeMetaData', 'shortname', 'id');

		for ($i = 0; $i < count($tag_names); $i++) {
			$ret = explode("\t", $values[$i]);
			if (!isset($ret[1]))
				continue;

			$shortname = strtolower($ret[0]);
			$value = $ret[1];

			$ret = explode("\t", $tag_names[$i]);
			$title = $ret[0];

			if (!in_array($shortname, $meta_data)) {
				$id = SwatDB::insertRow($this->db,
					'PinholeMetaData',
					array('text:shortname', 'text:title'),
					array('shortname' => $shortname,
						'title' => $title),
					'id');
			} else {
				$id = array_search($meta_data, $shortname);
			}

			SwatDB::insertRow($this->db, 'PinholePhotoMetaData',
				array('photo', 'meta_data', 'text:value'),
				array('photo' => $this->id, 'meta_data' => $id,
					'value' => $value));
		}
	}

	// }}}
	// {{{ private function fitToMax()

	private function fitToMax(Image_Transform $transformer,
		PinholeDimension $dimension)
	{
		if ($dimension->max_width !== null)
			$transformer->fitX($dimension->max_width);

		if ($dimension->max_height !== null)
			if ($transformer->new_y > $dimension->max_height)
				$transformer->fitY($dimension->max_height);
	}

	// }}}
	// {{{ private function cropToMax()

	private function cropToMax(Image_Transform $transformer,
		PinholeDimension $dimension)
	{
		$max_y = $dimension->max_height;
		$max_x = $dimension->max_width;

		if ($transformer->img_x / $max_x > $transformer->img_y / $max_y) {
			$new_y = $max_y;
			$new_x = ceil(($new_y / $transformer->img_y) * $transformer->img_x);
		} else {
			$new_x = $max_x;
			$new_y = ceil(($new_x / $transformer->img_x) * $transformer->img_y);
		}

		$transformer->resize($new_x, $new_y);

		// crop to fit
		if ($transformer->new_x != $max_x || $transformer->new_y != $max_y) {
			$offset_x = 0;
			$offset_y = 0;

			if ($transformer->new_x > $max_x)
				$offset_x = ceil(($transformer->new_x - $max_x) / 2);

			if ($transformer->new_y > $max_y)
				$offset_y = ceil(($transformer->new_y - $max_y) / 2);

			$transformer->crop($max_x, $max_y, $offset_x, $offset_y);
		}
	}

	// }}}

	// loader methods
	// {{{ protected function loadTags()

/*	protected function loadTags()
	{
		$sql = sprintf('select * from PinholeTag where id in (
			select tag from PinholePhotoTagBinding where photo = %s)',
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql, 'PinholeTagWrapper');
	}*/

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
