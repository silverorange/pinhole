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
	// {{{ protected properties

	protected $photo_ui;
	protected $photo_ui_xml = 'Pinhole/pages/browser-photo-view.xml';
	protected $page_size = 50;

	// }}}

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

		$this->layout->startCapture('dates');
		$this->displayDates();
		$this->layout->endCapture();

		$view = $this->photo_ui->getWidget('photo_view');
		$view->model = $this->getPhotoTableStore();

		$pagination = $this->photo_ui->getWidget('pagination');
		$pagination->total_records = $this->tag_intersection->getPhotoCount();
		$pagination->page_size = $this->page_size;
		$pagination->setCurrentPage($this->tag_intersection->getCurrentPage());
		$path = $this->tag_intersection->getIntersectingTagPath();
		$pagination->link = 'tag/';
		$pagination->link.= ($path === null) ? '' : $path.'/';
		$pagination->link.= 'site.page=%d';

		$this->layout->startCapture('content');
		$this->photo_ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function getPhotoTableStore()

	protected function getPhotoTableStore()
	{
		$store = new SwatTableStore();
		$photos = $this->tag_intersection->getPhotos($this->page_size);

		$tag_path = $this->tag_intersection->getIntersectingTagPath();
		$tag_path = ($tag_path === null) ? '' : '/'.$tag_path;

		foreach ($photos as $photo) {
			$ds = new SwatDetailsStore();
			$ds->path = $photo->id.$tag_path;
			$ds->photo = $photo;
			$store->add($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function displayDates()

	protected function displayDates()
	{
		$date_range = $this->tag_intersection->getDateRange();

		$this->displayYears($date_range);

		if ($date_range[0]->getYear() == $date_range[1]->getYear()) {
			$this->displayMonths($date_range);

			if ($date_range[0]->getMonth() == $date_range[1]->getMonth())
				$this->displayDays($date_range);
		}
	}

	// }}}
	// {{{ protected function displayYears()

	protected function displayYears($date_range)
	{
		$years = PinholePhoto::getDateRange($this->app->db);
		$photos = $this->tag_intersection->getPhotoCountByDate('year');

		if ($years === null)
			return;

		$year_start = $years[0]->getYear();
		$year_end = $years[1]->getYear();
		$date = new SwatDate();

		$span_tag = new SwatHtmlTag('span');
		$a_tag = new SwatHtmlTag('a');
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'years clearfix';
		$div_tag->open();

		// TODO: this should really just exclude Date tags, not only
		// include normal tags
		$current_intersection =
			$this->tag_intersection->getIntersectingTagPath(null,
				array('date.month', 'date.year', 'date.date'));

		for ($i = $year_start; $i <= $year_end; $i++) {
			$date->setYear($i);

			if (isset($photos[$i])) {
				$a_tag->title = sprintf('%d %s',
					$photos[$i],
					Pinhole::ngettext('photo', 'photos', $photos[$i]));
				$a_tag->class = ($date_range[0]->getYear() == $date_range[1]->getYear() &&
					$date_range[0]->getYear() == $i) ? 'selected' : null;
				$a_tag->href = sprintf('tag/%sdate.year=%s',
					($current_intersection == '') ? '' : $current_intersection.'/',
					$i);

				$a_tag->setContent($date->format('%Y'));
				$a_tag->display();
			} else {
				$span_tag->setContent($date->format('%Y'));
				$span_tag->display();
			}
		}

		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayMonths()

	protected function displayMonths($date_range)
	{
		$photos = $this->tag_intersection->getPhotoCountByDate('month');

		$date = new SwatDate();
		$date->setYear($date_range[0]->getYear());

		$a_tag = new SwatHtmlTag('a');
		$span_tag = new SwatHtmlTag('span');
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'months clearfix';
		$div_tag->open();

		// TODO: this should really just exclude Date tags, not only
		// include normal tags
		$current_intersection =
			$this->tag_intersection->getIntersectingTagPath(null,
				array('date.month', 'date.date', 'date.year'));

		for ($i = 1; $i <= 12; $i++) {
			$date->setMonth($i);
			$key = $date->format('%Y-%m');

			if (isset($photos[$key])) {
				$a_tag->title = sprintf('%d %s',
					$photos[$key],
					Pinhole::ngettext('photo', 'photos', $photos[$key]));
				$a_tag->class = ($date_range[0]->getMonth() == $date_range[1]->getMonth() &&
					$date_range[0]->getMonth() == $i) ? 'selected' : null;
				$a_tag->href = sprintf('tag/%sdate.month=%s/date.year=%s',
					($current_intersection == '') ? '' : $current_intersection.'/',
					$date->getMonth(),
					$date->getYear());
				$a_tag->setContent($date->format('%B'));
				$a_tag->display();
			} else {
				$span_tag->setContent($date->format('%B'));
				$span_tag->display();
			}
		}

		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayDays()

	protected function displayDays($date_range)
	{
		$photos = $this->tag_intersection->getPhotoCountByDate('day');

		$date = new SwatDate();
		$date->setMonth($date_range[0]->getMonth());
		$date->setYear($date_range[0]->getYear());

		$a_tag = new SwatHtmlTag('a');
		$span_tag = new SwatHtmlTag('span');
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'days clearfix';
		$div_tag->open();

		$current_intersection =
			$this->tag_intersection->getIntersectingTagPath(null,
				array('date.month', 'date.year', 'date.date'));

		for ($i = 1; $i <= $date->getDaysInMonth(); $i++) {
			$date->setDay($i);
			$key = $date->format('%Y-%m-%d');

			if (isset($photos[$key])) {
				$a_tag->title = sprintf('%d %s',
					$photos[$key],
					Pinhole::ngettext('photo', 'photos', $photos[$key]));
				$a_tag->class = ($date_range[0]->getDay() == $date_range[1]->getDay() &&
					$date_range[0]->getDay() == $i) ? 'selected' : null;
				$a_tag->href = sprintf('tag/%sdate.date=%s',
					($current_intersection == '') ? '' : $current_intersection.'/',
					$key);

				$a_tag->setContent($date->format('%d'));
				$a_tag->display();
			} else {
				$span_tag->setContent($date->format('%d'));
				$span_tag->display();
			}
		}

		$div_tag->close();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		return sprintf("var %s_obj = new HoverFade('%s');",'photo_view','photo_vie');
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->photo_ui->getRoot()->getHtmlHeadEntrySet());
	
		$this->layout->addHtmlHeadEntry(new SwatJavascriptHtmlHeadEntry(
			'packages/pinhole/javascript/pinhole-hover-fade.js',
			Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
