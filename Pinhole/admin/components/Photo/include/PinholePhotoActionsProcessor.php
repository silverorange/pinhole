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
			$this->page->app->getPage()->setItems($view->getSelection());
			break;

		case 'publish':
		case 'status_action':
			$num = count($view->getSelection());

			if ($ui->hasWidget('status_flydown'))
				$status = $ui->getWidget('status_flydown')->value;
			else
				$status = PinholePhoto::STATUS_PUBLISHED;

			foreach ($this->getPhotos($view) as $photo) {
				$photo->setStatus($status);
				$photo->save();
			}

			$message = new SwatMessage(sprintf(Pinhole::ngettext(
				'One photo has been updated to “%2$s”.',
				'%1$s photos have been updated to “%2$s”.', $num),
				SwatString::numberFormat($num),
				PinholePhoto::getStatusTitle($status)));

			$this->page->app->messages->add($message);
			break;

		case 'tags_action':
			$tag_list = $ui->getWidget('tags')->getSelectedTagList();
			$tag_list = $tag_list->getByType('PinholeTag');
			if (count($tag_list) > 0) {
				$tag_shortnames = array();
				foreach ($tag_list as $tag)
					$tag_shortnames[] = $tag->name;

				foreach ($this->getPhotos($view) as $photo)
					$photo->addTagsByShortname($tag_shortnames);

				$num = count($view->getSelection());
				if (count($tag_list) > 1) {
					$message = new SwatMessage(sprintf(Pinhole::ngettext(
						'Tags have been added to one photo.',
						'Tags have been added to %s photos.', $num),
						SwatString::numberFormat($num)));
				} else {
					$message = new SwatMessage(sprintf(Pinhole::ngettext(
						'A tag has been added to one photo.',
						'A tag has been added to %s photos.', $num),
						SwatString::numberFormat($num)));
				}

				$this->page->app->messages->add($message);
			}

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
		$instance_id = $app->instance->getId();

		$sql = sprintf('select PinholePhoto.* from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where PinholePhoto.id in (%s)
			and ImageSet.instance %s %s',
			$app->db->datatype->implodeArray($ids, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$app->db->quote($instance_id, 'integer'));

		return SwatDB::query($app->db, $sql,
			SwatDBClassMap::get('PinholePhotoWrapper'));
	}

	// }}}
}

?>
