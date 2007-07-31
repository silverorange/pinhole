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
	/**
	 * A reference to the page that is using this action processor
	 *
	 * @var AdminPage
	 */
	private $page;

	/**
	 * Creates a new photo action processor
	 *
	 * @param AdminPage $page the page that is using this action processor
	 */
	public function __construct(AdminPage $page)
	{
		$this->page = $page;
	}

	/**
	 * Processes actions on photos
	 *
	 * @param SwatTableView $view the view to process.
	 * @param SwatActions $actions the list of actions.
	 */
	public function process($view, $actions, $ui)
	{
		//TODO: enforce PinholeInstance for selected items

		switch ($actions->selected->id) {
		case 'delete':
			$this->page->app->replacePage('Photo/Delete');
			$this->page->app->getPage()->setItems($view->getSelection());
			break;
		case 'status_action':
			$num = count($view->getSelection());

			if ($ui->hasWidget('status_flydown'))
				$status = $ui->getWidget('status_flydown')->value;
			else
				$status = PinholePhoto::STATUS_PUBLISHED;

			SwatDB::updateColumn($this->page->app->db, 'PinholePhoto',
				'integer:status', $status,
				'id', $view->getSelection());

			if ($status == PinholePhoto::STATUS_PUBLISHED) {
				$publish_date = new SwatDate();

				SwatDB::updateColumn($this->page->app->db, 'PinholePhoto',
					'timestamp:publish_date', $publish_date,
					'id', $view->getSelection());
			}

			$message = new SwatMessage(sprintf(Pinhole::ngettext(
				'One photo has been updated to “%2$s”.',
				'%1$s photos have been updated to “%2$s”.', $num),
				SwatString::numberFormat($num),
				PinholePhoto::getStatusTitle($status)));

			$this->page->app->messages->add($message);
			break;
		case 'tags_action':
			$tags = $ui->getWidget('tags')->values;
			$tag_ids = array();
			foreach ($tags as $tag)
				$tag_ids[] = $this->page->app->db->quote($tag->id, 'integer');

			$sql = sprintf('insert into PinholePhotoTagBinding
				(photo, tag) select %%1$s, id from PinholeTag
				where id in (%s) and id not in (select tag
				from PinholePhotoTagBinding where photo = %%1$s)',
				implode(',', $tag_ids));

			foreach ($view->getSelection() as $id)
				SwatDB::query($this->page->app->db, sprintf($sql,
					$this->page->app->db->quote($id, 'integer')));

			$num = count($view->getSelection());

			if (count($tags) > 1)
				$message = new SwatMessage(sprintf(Pinhole::ngettext(
					'Tags have been added to one photo.',
					'Tags have been added to %s photos.', $num),
					SwatString::numberFormat($num)));
			else
				$message = new SwatMessage(sprintf(Pinhole::ngettext(
					'A tag has been added to one photo.',
					'A tag has been added to %s photos.', $num),
					SwatString::numberFormat($num)));

			$this->page->app->messages->add($message);
			break;
		}
	}
}

?>
