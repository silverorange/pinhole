<?php

require_once 'Image/Transform.php';
require_once 'Swat/SwatDate.php';
require_once 'Swat/exceptions/SwatFileNotFoundException.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Pinhole/dataobjects/PinholePhotoMetaDataBinding.php';
require_once 'Pinhole/dataobjects/PinholeDimensionWrapper.php';
require_once 'MDB2.php';

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
	// {{{ public function setWebRoot()

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
			return; //TODO: throw error

		$file = $_FILES[$name];

		$ext = strtolower(end(explode('.', $file['name'])));


		$filename = uniqid('file').'.'.$ext;
		$file_path = sprintf('%s/%s/%s',
			$this->path, $this->temp_path, $filename);

		move_uploaded_file($file['tmp_name'], $file_path);
		chmod($file_path, 0666);

		return $this->parseFile($filename, $file['name']);
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
	public function parseFile($filename, $original_filename = null)
	{
		$file_path = sprintf('%s/%s/%s',
			$this->path, $this->temp_path, $filename);

		if (!file_exists($file_path))
			throw new SwatFileNotFoundException(null, 0, $filename);

		$finfo = finfo_open(FILEINFO_MIME);
		$mime_type = finfo_file($finfo, $file_path);

		if (in_array($mime_type, $this->archive_mime_types))
			$files = $this->getArchivedFiles($filename,
				array_search($mime_type, $this->archive_mime_types));
		else
			$files = array($filename => $original_filename);

		return $files;
	}

	// }}}
	// {{{ public function processFile()

	/**
	 * TODO: update documentation
	 */
	public function processFile($filename, $original_filename = null)
	{
		$file_path = sprintf('%s/%s/%s',
			$this->path, $this->temp_path, $filename);

		if (!file_exists($file_path))
			throw new SwatFileNotFoundException(null, 0, $file_path);

		$processed_filename =
			$this->createDataObject($filename, $original_filename);

		unlink($file_path);

		return $processed_filename;
	}

	// }}}
	// {{{ protected function createDataObject()

	protected function createDataObject($filename, $original_filename)
	{
		//define('SWATDB_DEBUG', true);

		$file_path = sprintf('%s/%s/%s',
			$this->path, $this->temp_path, $filename);

		$this->db->beginTransaction();

		$photo = new PinholePhoto();
		$photo->setDatabase($this->db);

		$meta_data = $this->getMetaDataFromFile($filename);

		$photo->instance = 1; //TODO populate this correctly
		$photo->filename = sha1(uniqid(rand(), true));
		$photo->original_filename = $original_filename;

		// error suppression is needed here because there are several
		// ways unavoidable warnings can occur despite the file being
		// properly read.
		$photo->serialized_exif = serialize(@exif_read_data($file_path));
		$photo->upload_date = new SwatDate();

		if (isset($meta_data['createdate']))
			$photo->photo_date = $this->parseMetaDataDate(
				$meta_data['createdate']->value);

		$photo->save();

		$this->saveMetaData($photo, $meta_data);
		$this->saveDimensionsFromFile($filename, $photo);

		$this->db->commit();

		return $photo->filename;
	}

	// }}}
	// {{{ protected function getArchivedFiles()

	/**
	 * Processes an array of files
	 */
	protected function getArchivedFiles($filename, $type)
	{
		$file_path = sprintf('%s/%s/%s',
			$this->path, $this->temp_path, $filename);

		$za = new ZipArchive();
		$opened = $za->open($file_path);

		if ($opened !== true)
			return; //TODO: throw some sort of error or feedback for the user

		$dir = sprintf('%s/%s/%s',
			$this->path, $this->temp_path, uniqid('dir'));
		mkdir($dir);
		chmod($dir, 0770);

		// unfortunately, renaming the files in the archive and then
		// extracting them doesn't seem to work (extractTo doesn't
		// extract anything)
		$za->extractTo($dir);
		$za->close();

		unlink($file_path);

		$dh = opendir($dir);
		while (($file = readdir($dh)) !== false) {
			if ($file != '.' && $file != '..') {
				chmod($dir.'/'.$file, 0666);

				$ext = strtolower(end(explode('.', $file)));
				$filename = uniqid('file').'.'.$ext;
				$file_path = sprintf('%s/%s/%s',
					$this->path, $this->temp_path, $filename);

				$files[$filename] = $file;
				rename($dir.'/'.$file, $file_path);
			}
		}

		closedir($dh);
		rmdir($dir);

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
	protected function getMetaDataFromFile($filename)
	{
		$file_path = sprintf('%s/%s/%s',
			$this->path, $this->temp_path, $filename);

		exec("exiftool -t $file_path", $tag_names);
		exec("exiftool -t -s $file_path", $values);

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
		$existing_meta_data = SwatDB::getOptionArray($this->db,
			'PinholeMetaData', 'shortname', 'id');

		foreach ($meta_data as $data) {
			if (!in_array($data->shortname, $existing_meta_data)) {
				$meta_data_id = SwatDB::insertRow($this->db,
					'PinholeMetaData',
					array('text:shortname', 'text:title'),
					array('shortname' => $data->shortname,
						'title' => $data->title),
					'id');
			} else {
				$meta_data_id = array_search($data->shortname,
					$existing_meta_data);
			}

			SwatDB::insertRow($this->db, 'PinholePhotoMetaDataBinding',
				array('integer:photo',
					'integer:meta_data',
					'text:value'),
				array('photo' => $photo->id,
					'meta_data' => $meta_data_id,
					'value' => $data->value));
		}
	}

	// }}}
	// {{{ protected function saveDimensionsFromFile()

	protected function saveDimensionsFromFile($filename, PinholePhoto $photo)
	{
		static $dimensions;

		$file_path = sprintf('%s/%s/%s',
			$this->path, $this->temp_path, $filename);

		if ($dimensions === null)
			$dimensions = SwatDB::query($this->db,
				'select * from PinholeDimension',
				'PinholeDimensionWrapper');

		$transformer = Image_Transform::factory('Imagick2');
		if (PEAR::isError($transformer))
			throw new AdminException($transformer);

		foreach ($dimensions as $dimension) {
			// TODO: I don't think we want to load the file for
			// every dimension, but if I put it outside the loop,
			// the variable loses its file resource after the
			// first resize has taken place. (nick)
			$transformer->load($file_path);
			$transformed = $this->processImage($transformer, $dimension);

			SwatDB::insertRow($this->db, 'PinholePhotoDimensionBinding',
				array('integer:photo',
					'integer:dimension',
					'integer:width',
					'integer:height'),
				array('photo' => $photo->id,
					'dimension' => $dimension->id,
					'width' => $transformed->new_x,
					'height' => $transformed->new_y));

			$dimension_binding = new PinholePhotoDimensionBinding();
			$dimension_binding->photo = $photo;
			$dimension_binding->dimension = $dimension;

			$transformed->save($dimension_binding->getPath($this->path),
				false, $this->getCompressionQuality());
		}
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
