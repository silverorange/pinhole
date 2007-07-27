<?php

require_once 'Admin/pages/AdminXMLRPCServer.php';
require_once 'Pinhole/PinholePhotoFactory.php';

/**
 * An XML-RPC server for upload processing
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoUploadProcessorServer extends AdminXMLRPCServer
{
	// {{{ public function processFile()

	/** 
	 * Process a given image file
	 *
	 * @param string $file The file path to the image file 
	 * @param string $original_filename The original name of the file
	 *
	 * @return array An associative array entries 'filename' = the
	 *               temporary filename, 'processed_filename' = the saved
	 *               filename.
	 */
	public function processFile($filename, $original_filename)
	{
		$photo_factory = new PinholePhotoFactory();
		$photo_factory->setPath(realpath('../'));
		$photo_factory->setDatabase($this->app->db);

		$file = realpath('../../temp/'.$filename);
		$photo = $photo_factory->processPhoto($file, $original_filename);

		$response = array();
		$response['filename'] = $filename;

		if (PEAR::isError($photo))
			$response['error'] = 'Error processing '.$original_filename;
		else
			$response['processed_filename'] = $photo->filename;

		return $response;
	}

	// }}}
}

?>
