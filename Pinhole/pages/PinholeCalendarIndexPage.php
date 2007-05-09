<?php

require_once 'Swat/SwatString.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatUI.php';
require_once 'Swat/SwatDate.php';

require_once 'Pinhole/PinholeCalendarDisplay.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeCalendarIndexPage extends PinholePage
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

		/* Set YUI Grid CSS class for one full-width column on details page */
		$this->layout->data->yui_grid_class = 'yui-t3';
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->startCapture('header_content');
		$this->displayCurrentDate();
		$this->layout->endCapture();

		$this->layout->startCapture('sidebar_content');
		$this->displayCalendars();
		$this->layout->endCapture();

		$view = $this->photo_ui->getWidget('photo_view');
		$view->model = $this->getPhotoTableStore();

		$pagination = $this->photo_ui->getWidget('pagination');
		//$pagination->total_records = $this->tag_intersection->getPhotoCount();
		$pagination->page_size = 20;
		//$pagination->link = 'photo/'.((strlen($path) > 0) ? $path.'/' : '').'%s';

		$this->layout->startCapture('content');
		$this->photo_ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function getPhotoTableStore()

	protected function getPhotoTableStore()
	{
		$store = new SwatTableStore();
		//$photos = $this->tag_intersection->getPhotos(20);
		/*
		foreach ($photos as $photo) {
			$ds = $this->getPhotoDetailsStore($photo);
			$store->addRow($ds);
		}
		*/

		return $store;
	}

	// }}}
	// {{{ protected function getPhotoDetailsStore()

	protected function getPhotoDetailsStore($photo)
	{
		/*
		$ds = new SwatDetailsStore($photo);
		$ds->image = $photo->getDimension('thumb')->getURI();
		$ds->title = SwatString::condense($photo->title, 30);

		$path = $this->tag_intersection->getIntersectingTagPath();
		$ds->link = 'photo/'.((strlen($path) > 0) ? $path.'/' : '').$photo->id;

		$ds->width = $photo->getDimension('thumb')->width;
		$ds->max_width = $photo->getDimension('thumb')->dimension->max_width;
		$ds->height = $photo->getDimension('thumb')->height;
		$ds->max_height = $photo->getDimension('thumb')->dimension->max_height;

		return $ds;
		*/
	}

	// }}}
	// {{{ protected function displayCurrentDate()

	protected function displayCurrentDate()
	{
		echo 'Hello World';
	}

	// }}}
	// {{{ protected function displayCalendars()

	protected function displayCalendars()
	{
		$date = new SwatDate();
		$today = new SwatDate();

		for ($i = 1; $i <= 12; $i++) {
			$month = $date->getMonth();
			$year = $date->getYear();

			$cal = new PinholeCalendarDisplay('cal'.$i);
			$cal->display_month = $date;

			if ($date->getYear() == $today->getYear())
				$cal->addClassName('today', array($today->getDay()));

			$cal->addClassName('highlight', array(1, 15, 16, 25));

			$cal->display();

			$date->setYear(($month == 1) ? $year - 1 : $year);
			$date->setMonth(($month == 1) ? 12 : $month - 1);
		}
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/styles/pinhole-calendar-display.css', Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
