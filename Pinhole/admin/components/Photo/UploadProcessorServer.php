<?php

require_once 'Swat/exceptions/SwatException.php';
require_once 'Admin/pages/AdminXMLRPCServer.php';
require_once 'NateGoSearch/NateGoSearch.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'Pinhole/pages/PinholeSearchPage.php';

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
		$response = array();
		$response['filename'] = $filename;

		try {
			$class_name = SwatDBClassMap::get('PinholePhoto');
			$photo = new $class_name();
			$photo->setDatabase($this->app->db);
			$photo->setInstance($this->app->getInstance());
			$photo->setFileBase('../../photos');
			$photo->original_filename = $original_filename;
			$photo->process(sys_get_temp_dir().'/'.$filename);
			unlink(sys_get_temp_dir().'/'.$filename);

			$this->postProcessPhoto($photo);

			$response['id'] = $photo->id;
			$response['processed_filename'] = $photo->getFilename('thumb');

		} catch (SwatException $e) {
			$e->process();

			$response['error'] = 'Error processing '.$original_filename;
		} catch (Exception $e) {
			$e = new SwatException($e);
			$e->process();

			$response['error'] = 'Error processing '.$original_filename;
		}

		return $response;
	}

	// }}}
	// {{{ protected function postProcessPhoto()

	protected function postProcessPhoto(PinholePhoto $photo)
	{
		$type = NateGoSearch::getDocumentType($this->app->db, 'photo');

		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($photo->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($photo->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
}

?>
