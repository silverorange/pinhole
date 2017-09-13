<?php

/**
 * Centralized way to process photos
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoProcessor
{
	// {{{ protected properties

	/**
	 * @var SiteApplication
	 */
	protected $app;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app)
	{
		$this->app = $app;

		set_time_limit(30000);
		ini_set('memory_limit', -1);
		proc_nice(19);
	}

	// }}}
	// {{{ public function processPhoto()

	/**
	 * Process a photo
	 *
	 * @param mixed $photo The photo id or PinholePhoto dataobject to process
	 *
	 * @return PinholePhoto The processed photo
	 */
	public function processPhoto(PinholePhoto $photo)
	{
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
	}

	// }}}
	// {{{ protected function executeProcessing()

	protected function executeProcessing(PinholePhoto $photo)
	{
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
}

?>
