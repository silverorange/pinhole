<?php

// for the UI

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoProcessorServer extends AdminXMLRPCServer
{
	// {{{ protected properties

	protected $ui_xml = __DIR__.'/pending.xml';

	// }}}

	// process phase
	// {{{ public function processPhoto()

	/**
	 * Process a photo
	 *
	 * @param integer $id The photo id of the photo to process
	 *
	 * @return array An associative array of 'status', and optionally
	 *               'error_message', 'auto_publish', 'tile', and 'new_tags'
	 */
	public function processPhoto($id)
	{
		$photo = $this->getPhoto($id);
		$photo->setFileBase('../../photos');

		$processor = new PinholePhotoProcessor($this->app);
		$processor->processPhoto($photo);
		return $this->getResponse($photo);
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
			$response['new_tags'] = $this->getNewTags($photo);
			$response['tile'] = $this->getTile($photo);
		} else {
			$response['status'] = 'unknown';
		}

		return $response;
	}

	// }}}
	// {{{ protected function getNewTags()

	protected function getNewTags(PinholePhoto $photo)
	{
		$sql = sprintf('select title, id from
			PinholeTag where id in (
				select tag from PinholePhotoTagBinding where photo = %1$s)
				and id not in (select tag from PinholePhotoTagBinding
					where photo != %1$s)',
			$this->app->db->quote($photo->id, 'integer'));

		$tags = SwatDB::query($this->app->db, $sql);

		$response = array();
		foreach ($tags as $tag)
			$response[] = array('id' => $tag->id, 'title' => $tag->title);

		return $response;
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
		} elseif ($photo->image_set->instance !== null &&
			$photo->image_set->instance->id !== $instance_id) {
			throw new SiteNotFoundException('Photo '.$id.' accessed from the '.
				'wrong instance');
		}

		return $photo;
	}

	// }}}

	// display tile
	// {{{ protected function getTile()

	protected function getTile(PinholePhoto $photo)
	{
		$ui = new AdminUI();
		$ui->loadFromXML($this->ui_xml);

		$store = new SwatTableStore();
		$ds = new SwatDetailsStore();
		$ds->photo = $photo;
		$ds->class_name = $this->getTileClasses($photo);
		$store->add($ds);

		$ui->getWidget('index_view')->model = $store;

		ob_start();
		$ui->getWidget('index_view')->display();
		$string = ob_get_clean();

		// only pass back the tile element, not the whole view
		$dom = new DomDocument();
		$dom->loadXML('<xml>'.$string.'</xml>');
		$divs = $dom->getElementsByTagName('div');
		foreach ($divs as $div) {
			$classes = explode(' ', $div->getAttribute('class'));
			if (in_array('swat-tile', $classes))
				return $dom->saveXML($div);
		}
	}

	// }}}
	// {{{ protected function getTileClasses()

	protected function getTileClasses(PinholePhoto $photo)
	{
		$classes = array();

		if ($photo->private && $photo->isPublished())
			$classes[] = 'published-private';
		elseif ($photo->private)
			$classes[] = 'private';
		elseif ($photo->isPublished())
			$classes[] = 'published';

		return implode(' ', $classes);
	}

	// }}}
}

?>
