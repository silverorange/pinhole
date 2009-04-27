<?php

require_once 'Pinhole/admin/components/Photo/Pending.php';

/**
 * Pending photos page
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoLastUpload extends PinholePhotoPending
{
	// {{{ protected properties

	protected $unprocessed_photos;
	protected $upload_set_id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->unprocessed_photos = $this->getUnProcessedPhotos();

		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('select max(PinholePhoto.upload_set)
			from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where ImageSet.instance %s %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$this->upload_set_id = SwatDB::queryOne($this->app->db, $sql);

		// make the page size very large
		$this->ui->getWidget('pager')->page_size = 1000;

	}

	// }}}

	// build phase
	// {{{ protected function display()

	protected function display()
	{
		parent::display();

		if (count($this->unprocessed_photos) > 0)
			Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->ui->getWidget('index_frame')->title = Pinhole::_('Last Upload');
		$this->ui->getWidget('processing_message')->content =
			sprintf(Pinhole::ngettext(
				count($this->unprocessed_photos),
				'You have one photo waiting to be processed',
				'You have %s photos waiting to be processed'),
				count($this->unprocessed_photos));

		$tile_view = $this->ui->getWidget('index_view');
		$tile_view->check_all_visible_count +=
			count($this->unprocessed_photos);

		$tile_view->check_all_extended_count =
			count($this->unprocessed_photos) +
			$this->getAllPendingPhotosCount();

		if ($tile_view->check_all_extended_count >
			$tile_view->check_all_visible_count) {
			$tile_view->show_check_all = true;

			$frame = $this->ui->getWidget('index_frame');
			$frame->title_content_type = 'text/xml';
			$frame->subtitle = sprintf('<a href="Photo/Pending">%s</a>',
				sprintf(Pinhole::_('View all %s pending photos'),
				$tile_view->check_all_extended_count));
		}

		if (count($this->unprocessed_photos) > 0) {
			$tile_view->show_check_all = true;
			$this->ui->getWidget('index_actions')->visible = true;
			$this->ui->getWidget('processing_form')->visible = true;
		}
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$instance_id = $this->app->getInstanceId();

		return sprintf('PinholePhoto.status = %s
			and PinholePhoto.upload_set = %s
			and ImageSet.instance %s %s',
			$this->app->db->quote(PinholePhoto::STATUS_PENDING, 'integer'),
			$this->app->db->quote($this->upload_set_id, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		$unprocessed_photos_array = array();
		foreach ($this->unprocessed_photos as $photo)
			$unprocessed_photos_array[] = $photo->id;

		return sprintf('var page = new Pinhole.page.PendingPhotosPage([%s]);',
			implode(', ', $unprocessed_photos_array));
	}

	// }}}
	// {{{ protected function getAllPendingPhotosCount()

	protected function getAllPendingPhotosCount()
	{
		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('select count(PinholePhoto.id) from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where PinholePhoto.status = %s and ImageSet.instance %s %s',
			$this->app->db->quote(PinholePhoto::STATUS_PENDING, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		return SwatDB::queryOne($this->app->db, $sql);
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		if (count($this->unprocessed_photos) > 0) {
			$yui = new SwatYUI(array('dom', 'animation', 'event'));
			$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());

			$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
				'packages/pinhole/admin/javascript/pinhole-photo-pending.js',
				Pinhole::PACKAGE_ID));

			$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
				'packages/swat/javascript/swat-checkbox-cell-renderer.js',
				Swat::PACKAGE_ID));
		}
	}

	// }}}
}

?>
