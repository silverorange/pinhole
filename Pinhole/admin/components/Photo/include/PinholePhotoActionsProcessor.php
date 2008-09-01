<?php

require_once 'Admin/pages/AdminPage.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Processes actions on photos
 *
 * This class is used on both the photo search results and on the pending
 * photos page.
 *
 * @package   Pinhole
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoActionsProcessor
{
	// {{{ private properties

	/**
	 * A reference to the page that is using this action processor
	 *
	 * @var AdminPage
	 */
	private $page;

	// }}}

	// {{{ public function __construct()

	/**
	 * Creates a new photo action processor
	 *
	 * @param AdminPage $page the page that is using this action processor
	 */
	public function __construct(AdminPage $page)
	{
		$this->page = $page;
	}

	// }}}
	// {{{ public function process()

	/**
	 * Processes actions on photos
	 *
	 * @param SwatTableView $view the view to process.
	 * @param SwatActions $actions the list of actions.
	 */
	public function process($view, $actions, $ui)
	{
		switch ($actions->selected->id) {
		case 'delete':
			$this->page->app->replacePage('Photo/Delete');
			$this->page->app->getPage()->setItems($view->getSelection(),
				$view->isExtendedCheckAllSelected());

			break;

		case 'private':
			if ($ui->getWidget('passphrase')->value !== null) {
				$this->page->app->config->pinhole->passphrase =
					md5($ui->getWidget('passphrase')->value);

				$this->page->app->config->save(array('pinhole.passphrase'));
			}

			if ($this->page->app->config->pinhole->passphrase !== null) {
				$photos = $this->getPhotos($view);
				$num = count($photos);

				foreach ($photos as $photo) {
					$photo->private = true;
					$photo->save();
				}

				if (isset($this->app->memcache))
					$this->app->memcache->flushNs('photos');

				$message = new SwatMessage(sprintf(Pinhole::ngettext(
					'One photo has been set as private.',
					'%s photos have been set as private.', $num),
					SwatString::numberFormat($num)));

				$this->page->app->messages->add($message);
			}

			break;

		case 'public':
			$photos = $this->getPhotos($view);
			$num = count($photos);

			foreach ($photos as $photo) {
				$photo->private = true;
				$photo->save();
			}

			if (isset($this->app->memcache))
				$this->app->memcache->flushNs('photos');

			$message = new SwatMessage(sprintf(Pinhole::ngettext(
				'One photo has been set as public.',
				'%s photos have been set as public.', $num),
				SwatString::numberFormat($num)));

			$this->page->app->messages->add($message);
			break;

		case 'publish':
		case 'status_action':
			$photos = $this->getPhotos($view);
			$num = count($photos);

			if ($ui->hasWidget('status_flydown'))
				$status = $ui->getWidget('status_flydown')->value;
			else
				$status = PinholePhoto::STATUS_PUBLISHED;

			foreach ($photos as $photo) {
				$photo->setStatus($status);
				$photo->save();
			}

			if (isset($this->app->memcache))
				$this->app->memcache->flushNs('photos');

			$message = new SwatMessage(sprintf(Pinhole::ngettext(
				'One photo has been updated to “%2$s”.',
				'%1$s photos have been updated to “%2$s”.', $num),
				SwatString::numberFormat($num),
				PinholePhoto::getStatusTitle($status)));

			$this->page->app->messages->add($message);
			break;

		case 'tags_action':
			$tag_array = $ui->getWidget('tags')->getSelectedTagArray();
			if (count($tag_array) > 0) {
				$photos = $this->getPhotos($view);
				$num = count($photos);

				foreach ($photos as $photo)
					$photo->addTagsByName($tag_array);

				if (isset($this->app->memcache))
					$this->app->memcache->flushNs('photos');

				if (count($tag_array) > 1) {
					$message = new SwatMessage(sprintf(Pinhole::ngettext(
						'%s tags have been added to one photo.',
						'%s tags have been added to %s photos.', $num),
						SwatString::numberFormat(count($tag_array)),
						SwatString::numberFormat($num)));
				} else {
					$message = new SwatMessage(sprintf(Pinhole::ngettext(
						'A tag has been added to one photo.',
						'A tag has been added to %s photos.', $num),
						SwatString::numberFormat($num)));
				}

				$this->page->app->messages->add($message);
			}

			// reset tag list
			$ui->getWidget('tags')->setSelectedTagArray(array());

			break;
		}
	}

	// }}}
	// {{{ private function getPhotos()

	private function getPhotos(SwatView $view)
	{
		$ids = array();
		foreach ($view->getSelection() as $id)
			$ids[] = $id;

		$app = $this->page->app;
		$instance_id = $app->getInstanceId();

		$sql = sprintf('select PinholePhoto.* from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where ImageSet.instance %s %s',
			SwatDB::equalityOperator($instance_id),
			$app->db->quote($instance_id, 'integer'));

		// note the only page with an extended-selection that accesses this
		// is the pending photos page - so enforce status here.
		if ($view->isExtendedCheckAllSelected()) {
			$sql.= sprintf(' and PinholePhoto.status = %s',
				$app->db->quote(PinholePhoto::STATUS_PENDING, 'integer'));
		} else {
			$sql.= sprintf(' and PinholePhoto.id in (%s)',
				$app->db->datatype->implodeArray($ids, 'integer'));
		}

		$photos = SwatDB::query($app->db, $sql,
			SwatDBClassMap::get('PinholePhotoWrapper'));

		foreach ($photos as $photo)
			$photo->setInstance($app->getInstance());

		return $photos;
	}

	// }}}
}

?>
