<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'include/PinholeAdminPhotoCellRenderer.php';

/**
 * Delete confirmation page for Photos
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoDelete extends AdminDBDelete
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		// override default xml
		$this->ui = new AdminUI();
		$this->ui->loadFromXML(dirname(__FILE__).'/delete.xml');
	}

	// }}}
	// {{{ protected function getPhotos()

	protected function getPhotos()
	{
		$item_list = $this->getItemList('integer');

		return PinholePhotoWrapper::loadSetFromDBWithDimension(
			$this->app->db, 'thumb',
			'PinholePhoto.id in ('.$item_list.')');
	}

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$photos = $this->getPhotos();
		$num = count($photos);

		foreach ($photos as $photo)
			$photo->delete();

		$message = new SwatMessage(sprintf(Pinhole::ngettext(
			'One photo has been deleted.', '%d photos have been deleted.',
			$num), SwatString::numberFormat($num)), SwatMessage::NOTIFICATION);

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$container = $this->ui->getWidget('confirmation_container');
		$delete_view = $this->ui->getWidget('delete_view');

		$store = new SwatTableStore();

		foreach ($this->getPhotos() as $photo) {
			$ds = new SwatDetailsStore($photo);
			$ds->photo = $photo;
			$store->add($ds);
		}

		$delete_view->model = $store;

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = sprintf('<strong>Are you sure you want to delete
			the following %s?</strong>',
			Pinhole::ngettext('photo', 'photos', count($store)));
		$message->content_type = 'text/xml';

		$this->buildNavBar();
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$this->navbar->addEntry(new SwatNavBarEntry(Pinhole::_('Delete')));
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/admin/styles/pinhole-photo-tile.css',
			Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
