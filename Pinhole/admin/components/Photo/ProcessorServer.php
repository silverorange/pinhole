<?php

require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'Admin/pages/AdminXMLRPCServer.php';
require_once 'NateGoSearch/NateGoSearch.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoProcessorServer extends AdminXMLRPCServer
{
	// process phase
	// {{{ public function processPhoto()

	/**
	 * Process a photo
	 *
	 * @param integer $id The photo id of the photo to process
	 *
	 * @return array An associative array of 'status', and optionally
	 *               'error_message', 'auto_publish', and 'image_uri'
	 */
	public function processPhoto($id)
	{
		$photo = $this->getPhoto($id);

		if ($photo->id !== null && !$photo->isProcessed()) {
			try {
				$this->executeProcessing($photo);
			} catch (SwatException $e) {
				$e->log();
				$photo->status = PinholePhoto::STATUS_PROCESSING_ERROR;
			} catch (Exception $e) {
				$e = new SwatException($e);
				$e->log();
				$photo->status = PinholePhoto::STATUS_PROCESSING_ERROR;
			}

			$photo->save();
			$this->clearCache($photo);
			$this->addToSearchQueue($photo);
		}

		return $this->getResponse($photo);
	}

	// }}}
	// {{{ protected function getPhoto()

	protected function getPhoto($id)
	{
		$class_name = SwatDBClassMap::get('PinholePhoto');
		$photo = new $class_name();
		$photo->setDatabase($this->app->db);
		$photo->load($id);

		$instance_id = $this->app->getInstanceId();

		if ($photo->id === null) {
			throw new SiteNotFoundException('Photo '.$id.' not found');
		} elseif ($photo->image_set->instance->id !== $instance_id) {
			throw new SiteNotFoundException('Photo '.$id.' accessed from the '.
				'wrong instance');
		}

		return $photo;
	}

	// }}}
	// {{{ protected function executeProcessing()

	protected function executeProcessing(PinholePhoto $photo)
	{
		$photo->setFileBase('../../photos');
		$photo->process($this->getFilePath($photo));

		if ($photo->auto_publish)
			$photo->publish();
		else
			$photo->status = PinholePhoto::STATUS_PENDING;
	}

	// }}}
	// {{{ protected function clearCache()

	protected function clearCache(PinholePhoto $photo)
	{
		if (isset($this->app->memcache) &&
			$photo->status == PinholePhoto::STATUS_PUBLISHED) {
			$this->app->memcache->flushNs('photos');
		}
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue(PinholePhoto $photo)
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
	// {{{ protected function getFilePath()

	protected function getFilePath(PinholePhoto $photo)
	{
		return sprintf('%s/%s',
			sys_get_temp_dir(),	$photo->temp_filename);
	}

	// }}}
	// {{{ protected function getResponse()

	protected function getResponse(PinholePhoto $photo)
	{
		$response = array();

		if ($photo->status === PinholePhoto::STATUS_PROCESSING_ERROR) {
			$response['status'] = 'error';
			$response['error_message'] =
				sprintf(Pinhole::_('Error processing file %s'),
					$photo->original_filename);
		} elseif ($photo->isProcessed()) {
			$response['status'] = 'processed';
			$response['auto_publish'] = $photo->auto_publish;
			$response['image_uri'] = $photo->getUri('thumb');
		} else {
			$response['status'] = 'unknown';
		}

		return $response;
	}

	// }}}
}

?>
