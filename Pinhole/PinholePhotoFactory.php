<?php

require_once 'Image/Transform.php';
require_once "File/Archive.php";
require_once 'Swat/exceptions/SwatFileNotFoundException.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
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

	protected $path;

	protected $db;

	protected $archive_mime_types = array(
		'zip'  => 'application/x-zip',
		'gzip' => 'application/x-gzip',
		'tar'  => 'application/x-tar',
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
	// {{{ public function process()

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
	public function process($file, $original_filename = null)
	{
		if (!file_exists($file))
			throw new SwatFileNotFoundException(null, 0, $file);

		$finfo = finfo_open(FILEINFO_MIME);
		$mime_type = finfo_file($finfo, $file);

		if (in_array($mime_type, $this->archive_mime_types))
			$files = $this->getArchivedFiles($file,
				array_search($mime_type, $this->archive_mime_types));
		else
			$files = array($file => $original_filename);

		foreach ($files as $file => $original_filename)
			$this->createDataObject($file, $original_filename);
	}

	// }}}
	// {{{ public function processUploadedFile()

	/**
	 * Process a file that has been uploaded
	 *
	 * @param string $name Name of the file input
	 */
	public function processUploadedFile($name)
	{
		if (!isset($_FILES[$name]))
			return; //TODO: throw error

		$file = $_FILES[$name];

		$ext = strtolower(end(explode('.', $file['name'])));

		$file_path = sprintf('%s/../temp/%s.%s',
			$this->path, uniqid('file'), $ext);

		move_uploaded_file($file['tmp_name'], $file_path);
		chmod($file_path, 0666);

		$this->process($file_path, $file['name']);

		unlink($file_path);
	}

	// }}}
	// {{{ protected function createDataObject()

	protected function createDataObject($file, $original_filename)
	{
		//define('SWATDB_DEBUG', true);

		$this->db->beginTransaction();

		$photo = new PinholePhoto();
		$photo->setDatabase($this->db);

		$photo->filename = sha1(uniqid(rand(), true));
		$photo->original_filename = ($original_filename === null) ?
			$file : $original_filename;

		$photo->upload_date = new SwatDate();
		$photo->serialized_exif = serialize(exif_read_data($file));
		$photo->save();

		$this->saveMetaDataFromFile($file, $photo);
		$this->saveDimensionsFromFile($file, $photo);

		$this->db->commit();
	}

	// }}}
	// {{{ protected function getArchivedFiles()

	/**
	 * Processes an array of files
	 */
	protected function getArchivedFiles($archive, $type)
	{
		$files = array();

		$dir = $this->path.'/../temp/'.uniqid('dir');
		mkdir($dir);

		File_Archive::extract(
			File_Archive::readArchive($type, $archive),
			File_Archive::appender($dir));

		//TODO: - fix file permissions problem
		//      - fix read_exif_data error
		//	- sort out where File_Archive is throwing as pass by ref
		//	  error
		//	- see if there's cleaner way to access the files in the
		//	  archive than moving them around like this

		$dh = opendir($dir);
		while (($file = readdir($dh)) !== false) {
			if ($file != '.' && $file != '..') {
				chmod($dir.'/'.$file, 0666);

				$ext = strtolower(end(explode('.', $file)));
				$file_path = $this->path.'/../temp/'.uniqid('file').'.jpg';
				$files[$file_path] = $file;
				rename($dir.'/'.$file, $file_path);
			}
		}
		closedir($dh);

		unlink($archive);
		rmdir($dir);

		return $files;
	}

	// }}}
	// {{{ protected function saveMetaDataFromFile()

	/**
	 * Get the meta data from a photo
	 *
	 * @param string $filename 
	 * @return array An array of PinholeMetaData dataobjects 
	 */
	protected function saveMetaDataFromFile($filename, PinholePhoto $photo)
	{
		exec("exiftool -t $filename", $tag_names);
		exec("exiftool -t -s $filename", $values);

		$meta_data = SwatDB::getOptionArray($this->db,
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
				$meta_data_id = SwatDB::insertRow($this->db,
					'PinholeMetaData',
					array('text:shortname', 'text:title'),
					array('shortname' => $shortname,
						'title' => $title),
					'id');
			} else {
				$meta_data_id = array_search($shortname, $meta_data);
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
	// {{{ protected function saveDimensionsFromFile()

	protected function saveDimensionsFromFile($file, PinholePhoto $photo)
	{
		static $dimensions;

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
			$transformer->load($file);
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
}

?>
