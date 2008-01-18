<?php

require_once 'MDB2.php';
require_once 'Image/Transform.php';
require_once 'Swat/SwatDate.php';
require_once 'Swat/exceptions/SwatFileNotFoundException.php';
require_once 'Site/dataobjects/SiteInstance.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Pinhole/dataobjects/PinholePhotoMetaDataBinding.php';
require_once 'Pinhole/dataobjects/PinholeDimensionWrapper.php';
require_once 'Pinhole/exceptions/PinholeUploadException.php';
require_once 'Pinhole/exceptions/PinholeProcessingException.php';

/**
 * Photo factory
 *
 * The photo factory is responsible for creating photo objects from a file. If
 * an archive file is used, the factory will extract the image files and
 * process them.
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoFactory
{
	// {{{ protected properties

	protected $temp_path = '../temp';

	protected $path;

	protected $db;

	protected $archive_mime_types = array(
		'zip'  => 'application/x-zip',
		);

	protected $photo_id;

	// }}}
	// {{{ public function setDatabase()

	/**
	 * Sets the database for this factory 
	 *
	 * @param MDB2_Driver_Common $db optional. The database connection to use
	 *                                for creating dataobjects.
	 */
	public function setDatabase(MDB2_Driver_Common $db)
	{
		$this->db = $db;
	}

	// }}}
	// {{{ public function setInstance()

	/**
	 * Sets the instance for this factory
	 *
	 * @param Instance $instance The Instance this file
	 *                        belongs to.
	 */
	public function setInstance(SiteInstance $instance)
	{
		$this->instance = $instance;
	}

	// }}}
	// {{{ public function setPath()

	/**
	 * Sets the path to the web root 
	 *
	 * @param $path Path to the web root. 
	 */
	public function setPath($path)
	{
		$this->path = $path;
	}

	// }}}
	// {{{ public function saveUploadedFile()

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
	public function saveUploadedFile($name)
	{
		if (!isset($_FILES[$name]))
			throw new PinholeUploadException(
				'File input name not found.');

		$file = $_FILES[$name];

		$ext = strtolower(end(explode('.', $file['name'])));

		$filename = uniqid('file').'.'.$ext;
		$file_path = sprintf('%s/%s/%s',
			$this->path, $this->temp_path, $filename);

		move_uploaded_file($file['tmp_name'], $file_path);
		chmod($file_path, 0666);

		return $this->parseFile($file_path, $file['name']);
	}

	// }}}
	// {{{ public function parseFile()

	/**
	 * TODO: update documentation
	 * Parses a tag from a tag string and returns an appropriate tag object
	 *
	 * @param string $string the tag string to parse.
	 * @param MDB2_Driver_Common $db optional. The database connection to use
	 *                                for the parsed tag. If not specified,
	 *                                the default database specified by the
	 *                                tag factory is used.
	 *
	 * @return PinholeAbstractTag the parsed tag object or null if the given
	 *                             string could not be parsed.
	 */
	public function parseFile($file, $original_filename = null)
	{
		if (!file_exists($file))
			throw new PinholeProcessingException(
				'File could not be found.');

		$finfo = finfo_open(FILEINFO_MIME);
		$mime_type = finfo_file($finfo, $file);

		if (in_array($mime_type, $this->archive_mime_types))
			$files = $this->getArchivedFiles($file,
				array_search($mime_type, $this->archive_mime_types));
		else
			$files = array(basename($file) => $original_filename);

		return $files;
	}

	// }}}
	// {{{ public function processPhoto()

	public function processPhoto($file, $original_filename = null)
	{
		if (!file_exists($file))
			throw new PinholeProcessingException(
				'File could not be found.');

		if ($this->db === null)
			throw new PinholeProcessingException(
				'Database must be set before creating a data-object. '.
				'See: PinholePhotoFactory::setDatabase().');

		$this->db->beginTransaction();

		$photo = new PinholePhoto();
		$photo->setDatabase($this->db);

		$meta_data = $this->getMetaDataFromFile($file);

		$photo->instance = $this->instance;
		$photo->filename = sha1(uniqid(rand(), true));
		$photo->original_filename = $original_filename;
		$photo->upload_date = new SwatDate();
		if (isset($meta_data['createdate']))
			$photo->photo_date = $this->parseMetaDataDate(
				$meta_data['createdate']->value);

		// error suppression is needed here because there are several
		// ways unavoidable warnings can occur despite the file being
		// properly read.
		$exif = @exif_read_data($file);
		if ($exif !== null)
			$photo->serialized_exif = base64_encode(serialize($exif));

		// save photo
		$photo->save();
		
		// set the id so that we can save the timezone
		$this->photo_id = $photo->id;

		$saved = $this->resizeFile($file, $photo, true);
		if (PEAR::isError($saved)) {
			$this->db->rollback();
			throw new PinholeProcessingException($saved);
		}

		$this->saveMetaData($photo, $meta_data);

		$this->db->commit();

		unlink($file);

		return $photo;
	}

	// }}}
	// {{{ public function resizeFile()

	public function resizeFile($file, $photo)
	{
		static $dimensions;

		if ($dimensions === null)
			$dimensions = SwatDB::query($this->db,
				sprintf('select * from PinholeDimension where instance %s %s',
					SwatDB::equalityOperator($this->instance->getId()),
					$this->db->quote($this->instance->getId(), 'integer')),
				'PinholeDimensionWrapper');

		$transformer = Image_Transform::factory('Imagick2');
		if (PEAR::isError($transformer))
			throw new PinholeProcessingException($transformer);

		$transformations = array();

		foreach ($dimensions as $dimension) {
			// TODO: I don't think we want to load the file for
			// every dimension, but if I put it outside the loop,
			// the variable loses its file resource after the
			// first resize has taken place. (nick)
			$loaded = $transformer->load($file);

			if (PEAR::isError($loaded))
				throw new PinholeProcessingException($loaded);

			$dimension_binding = new PinholePhotoDimensionBinding();
			$dimension_binding->photo = $photo;
			$dimension_binding->dimension = $dimension;

			if ($dimension->shortname === PinholeDimension::DIMENSION_ORIGINAL) {
				copy($file, $dimension_binding->getPath($this->path));

				$this->saveDimension($photo, $dimension,
					$transformer->img_x, $transformer->img_y);
			} else {

				$transformed = $this->processImage($transformer, $dimension);

				if (PEAR::isError($transformed))
					throw new PinholeProcessingException($transformed);

				$transformed->save($dimension_binding->getPath($this->path),
					false, $this->getCompressionQuality());

				$this->saveDimension($photo, $dimension,
					$transformed->new_x, $transformed->new_y);
			}
		}

		return $transformations;
	}

	// }}}
	// {{{ public function getPhotoId()

	public function getPhotoId()
	{
		return $this->photo_id;
	}

	// }}}
	// {{{ protected function getArchivedFiles()

	/**
	 * Processes an array of files
	 */
	protected function getArchivedFiles($file, $type)
	{
		$za = new ZipArchive();
		$opened = $za->open($file);

		if ($opened !== true)
			throw new PinholeProcessingException(
				'Error opening file archive');

		$file_path = sprintf('%s/%s',
			$this->path, $this->temp_path);

		for ($i = 0; $i < $za->numFiles; $i++) {
			$stat = $za->statIndex($i);

			$ext = strtolower(end(explode('.', $stat['name'])));

			// TODO: we probably need a better way to keep from
			// extracting sub-dirs (mac archive files contain
			// sub-dirs with system files)
			if ($stat['size'] == 0 ||
				strpos($stat['name'], '/') !== false)
				continue;

			$filename = uniqid('file').'.'.$ext;
			$files[$filename] = $stat['name'];

			$za->renameIndex($i, $filename);
			$za->extractTo($file_path, $filename);
			chmod($file_path.'/'.$filename, 0666);
		}

		$za->close();

		unlink($file);

		return $files;
	}

	// }}}
	// {{{ protected function getMetaDataFromFile()

	/**
	 * Get the meta data from a photo
	 *
	 * @param string $filename
	 *
	 * @return array An array of PinholePhotoMetaDataBinding data objects
	 *               with $shortname as the key of the array.
	 */
	protected function getMetaDataFromFile($file)
	{
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
	// {{{ protected function saveMetaData()

	/**
	 * Get the meta data from a photo
	 *
	 * @param string $filename 
	 * @return array An array of PinholeMetaData dataobjects 
	 */
	protected function saveMetaData(PinholePhoto $photo, $meta_data)
	{
		$where_clause = sprintf('PinholeMetaData.instance %s %s',
			SwatDB::equalityOperator($this->instance->getId()),
			$this->db->quote($this->instance->getId, 'integer'));

		$existing_meta_data = SwatDB::getOptionArray($this->db,
			'PinholeMetaData', 'shortname', 'id', null,
			$where_clause);

		foreach ($meta_data as $data) {
			$shortname = substr($data->shortname, 0, 255);
			$title = substr($data->title, 0, 255);
			$value = substr($data->value, 0, 255);

			if (!in_array($shortname, $existing_meta_data)) {
				$meta_data_id = SwatDB::insertRow($this->db,
					'PinholeMetaData',
					array('text:shortname', 'text:title',
						'integer:instance'),
					array('shortname' => $shortname,
						'title' => $title,
						'instance' => $this->instance->getId()),
					'id');
			} else {
				$meta_data_id = array_search($shortname,
					$existing_meta_data);
			}

			SwatDB::insertRow($this->db, 'PinholePhotoMetaDataBinding',
				array('integer:photo',
					'integer:meta_data',
					'text:value'),
				array('photo' => $photo->id,
					'meta_data' => $meta_data_id,
					'value' => $value));
		}
	}

	// }}}
	// {{{ protected function saveDimension()

	protected function saveDimension(PinholePhoto $photo,
		PinholeDimension $dimension, $width, $height)
	{
		SwatDB::insertRow($this->db, 'PinholePhotoDimensionBinding',
			array('integer:photo',
				'integer:dimension',
				'integer:width',
				'integer:height'),
			array('photo' => $photo->id,
				'dimension' => $dimension->id,
				'width' => $width,
				'height' => $height));

		return true;
	}

	// }}}
	// {{{ protected function processImage()

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
	protected function processImage(Image_Transform $transformer,
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
	// {{{ protected function getDpi()

	protected function getDpi()
	{
		return 72;
	}

	// }}}
	// {{{ protected function getCompressionQuality()

	// TODO: move this to Dimension object
	protected function getCompressionQuality()
	{
		return 85;
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
	// {{{ private function parseMetaDataDate()

	private function parseMetaDataDate($date)
	{
		list($year, $month, $day, $hour, $minute, $second) =
			sscanf($date, "%d:%d:%d %d:%d:%d");

		if ($second === null)
			return null;

		$date = new SwatDate();
		$date->setYear($year);
		$date->setMonth($month);
		$date->setDay($day);
		$date->setHour($hour);
		$date->setMinute($minute);
		$date->setSecond($second);

		return $date;
	}

	// }}}
}

?>
