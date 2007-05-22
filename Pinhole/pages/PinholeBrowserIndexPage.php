<?php

require_once 'Swat/SwatString.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatUI.php';
require_once 'Pinhole/pages/PinholeBrowserPage.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeBrowserIndexPage extends PinholeBrowserPage
{
	protected $photo_ui;
	protected $photo_ui_xml = 'Pinhole/pages/browser-photo-view.xml';

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->photo_ui = new SwatUI();
		$this->photo_ui->mapClassPrefixToPath('Pinhole', 'Pinhole');
		$this->photo_ui->loadFromXML($this->photo_ui_xml);
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$view = $this->photo_ui->getWidget('photo_view');
		$view->model = $this->getPhotoTableStore();

		$pagination = $this->photo_ui->getWidget('pagination');
		$pagination->total_records = $this->tag_intersection->getPhotoCount();
		$pagination->page_size = 100;
		$pagination->setCurrentPage($this->tag_intersection->getCurrentPage());
		$path = $this->tag_intersection->getIntersectingTagPath();
		$pagination->link = 'tag/'.((strlen($path) > 0) ? $path.'/' : '').
			'site.page=%d';

		$this->layout->startCapture('content');
		$this->photo_ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function getPhotoTableStore()

	protected function getPhotoTableStore()
	{
		$store = new SwatTableStore();
		// TODO: add pagination to UI and pass limit and offest to the
		//       getPhotos() method below:
		$photos = $this->tag_intersection->getPhotos(100);

		foreach ($photos as $photo) {
			$ds = new SwatDetailsStore();
			$ds->photo = $photo;
			$store->addRow($ds);
		}

		return $store;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->photo_ui->getRoot()->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
