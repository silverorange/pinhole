<?php

require_once 'Swat/SwatString.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatUI.php';
require_once 'Swat/SwatDate.php';

require_once 'Pinhole/PinholeCalendarDisplay.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeCalendarIndexPage extends PinholePage
{
	// {{{ protected properties

	protected $photo_ui;
	protected $photo_ui_xml = 'Pinhole/pages/browser-photo-view.xml';

	protected $date_start;
	protected $date_end;
	protected $date_parts = array();
	protected $current_page = 0;
	protected $page_size = 60;

	protected $calendar_start_date;
	protected $calendar_end_date;
	protected $calendar_photo_count_array;
	// }}}

	// init phase
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout,
		$date_parts = null, $current_page = 0)
	{
		parent::__construct($app, $layout);

		if ($date_parts !== null)
			$this->date_parts = explode('/', $date_parts);

		$this->current_page = $current_page;
	}

	// }}}
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->photo_ui = new SwatUI();
		$this->photo_ui->mapClassPrefixToPath('Pinhole', 'Pinhole');
		$this->photo_ui->loadFromXML($this->photo_ui_xml);

		$this->setDateRange();
	}

	// }}}
	// {{{ protected function setDateRange()

	protected function setDateRange()
	{
		if (isset($this->date_parts[0])) {
			$this->start_date = new SwatDate();
			$this->start_date->setYear($this->date_parts[0]);
			$this->start_date->setMonth(isset($this->date_parts[1]) ? $this->date_parts[1] : 1);
			$this->start_date->setDay(isset($this->date_parts[2]) ? $this->date_parts[2] : 1);
		} else {
			$sql = sprintf('select max(photo_date) from PinholePhoto
				where status = %s',
				$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer'));

			$this->start_date = new SwatDate(
				SwatDB::queryOne($this->app->db, $sql));
		}

		$this->start_date->setHour(0);
		$this->start_date->setMinute(0);
		$this->start_date->setSecond(0);

		$this->end_date = clone $this->start_date;

		if (isset($this->date_parts[3]) && $this->date_parts[3] == 'week') {
			$this->end_date->addSeconds(7 * 86400);
			$this->layout->data->title = sprintf('Week of %s',
				$this->start_date->format(SwatDate::DF_DATE_LONG));
		} elseif (!isset($this->date_parts[0]) || isset($this->date_parts[2])) {
			$this->end_date->addSeconds(86400);
			$this->layout->data->title =
				$this->start_date->format(SwatDate::DF_DATE_LONG);
		} elseif (isset($this->date_parts[1])) {
			$this->end_date->addSeconds($this->start_date->getDaysInMonth() * 86400);
			$this->layout->data->title =
				$this->start_date->format(SwatDate::DF_MY);
		} else {
			$this->end_date->setYear($this->start_date->getYear() + 1);
			$this->layout->data->title =
				$this->start_date->format('%Y');
		}

		$this->calendar_end_date = new SwatDate();
		$this->calendar_end_date->setHour(23);
		$this->calendar_end_date->setMinute(59);
		$this->calendar_end_date->setSecond(59);
		$this->calendar_end_date->setDay($this->calendar_end_date->getDaysInMonth());

		if (!isset($this->date_parts[1]))
			$this->calendar_end_date->setMonth(12);

		if ($this->calendar_end_date->getMonth() < $this->start_date->getMonth())
			$this->calendar_end_date->setYear($this->start_date->getYear() + 1);
		else
			$this->calendar_end_date->setYear($this->start_date->getYear());

		$this->calendar_start_date = clone $this->calendar_end_date;
		$this->calendar_start_date->setYear($this->calendar_start_date->getYear() - 1);
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->startCapture('header_content');
		echo $this->layout->data->title;
		$this->layout->endCapture();

		$this->layout->startCapture('sidebar_content');
		$this->displayNextPrevDate();
		$this->displayCalendars();
		$this->layout->endCapture();

		$view = $this->photo_ui->getWidget('photo_view');
		$view->model = $this->getPhotoTableStore($this->page_size,
			$this->current_page * $this->page_size);

		$pagination = $this->photo_ui->getWidget('pagination');
		$pagination->total_records = $this->getPhotoCount();
		$pagination->page_size = $this->page_size;
		$pagination->setCurrentPage($this->current_page);
		$pagination->link = sprintf('calendar/%s/page%%s',
			implode('/', $this->date_parts));

		$this->layout->startCapture('content');
		$this->photo_ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function getPhotoTableStore()

	protected function getPhotoTableStore($limit = null, $offset = 0)
	{
		$photos = PinholePhotoWrapper::loadSetFromDBWithDimension(
			$this->app->db, 'thumb', $this->getWhereClause(),
			'', $limit, $offset);

		$store = new SwatTableStore();

		foreach ($photos as $photo) {
			$ds = $this->getPhotoDetailsStore($photo);
			$store->addRow($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function getPhotoDetailsStore()

	protected function getPhotoDetailsStore($photo)
	{
		$ds = new SwatDetailsStore($photo);
		$ds->image = $photo->getDimension('thumb')->getURI();
		$ds->title = SwatString::condense($photo->title, 30);
		$ds->link = 'photo/'.$photo->id;
		$ds->width = $photo->getDimension('thumb')->width;
		$ds->max_width = $photo->getDimension('thumb')->dimension->max_width;
		$ds->height = $photo->getDimension('thumb')->height;
		$ds->max_height = $photo->getDimension('thumb')->dimension->max_height;

		return $ds;
	}

	// }}}
	// {{{ protected function getPhotoCount()

	protected function getPhotoCount()
	{
		$sql = sprintf('select count(id) from PinholePhoto
			where %s',
			$this->getWhereClause());

		return SwatDB::queryOne($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		return sprintf('PinholePhoto.status = %s and
			 PinholePhoto.photo_date >= %s
			and PinholePhoto.photo_date < %s',
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer'),
			$this->app->db->quote($this->start_date->getDate(), 'date'),
			$this->app->db->quote($this->end_date->getDate(), 'date'));
	}

	// }}}
	// {{{ protected function displayCalendars()

	protected function displayCalendars()
	{
		$date = clone $this->calendar_end_date;

		for ($i = 1; $i <= 12; $i++) {
			$month = $date->getMonth();
			$year = $date->getYear();

			$cal = new PinholeCalendarDisplay('cal'.$i);
			$cal->link = 'calendar/%s';
			$cal->setMonth($date);

			$cal->setSelectedDateRange($this->start_date, $this->end_date);
			$cal->setHighlightedDays($this->getPhotoCountArray($date));

			$cal->display();

			$date->setYear(($month == 1) ? $year - 1 : $year);
			$date->setMonth(($month == 1) ? 12 : $month - 1);
		}
	}

	// }}}
	// {{{ protected function getPhotoCountArray()

	protected function getPhotoCountArray($date)
	{
		if ($this->calendar_photo_count_array === null) {
			$sql = sprintf('select photo_count, photo_date
				from PinholePhotoCountByDateView
				where PinholePhotoCountByDateView.status = %s
					and PinholePhotoCountByDateView.photo_date >= %s
					and PinholePhotoCountByDateView.photo_date < %s',
				$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer'),
				$this->app->db->quote($this->calendar_start_date->getDate(), 'date'),
				$this->app->db->quote($this->calendar_end_date->getDate(), 'date'));

			$rs = SwatDB::query($this->app->db, $sql);

			$this->calendar_photo_count_array = array();

			foreach ($rs as $row) {
				$photo_date = new SwatDate($row->photo_date);
				$y = $photo_date->getYear();
				$m = $photo_date->getMonth();
				$d = $photo_date->getDay();

				$this->calendar_photo_count_array[$y.'/'.$m][$d] = 
					sprintf('%d %s',
						$row->photo_count,
						Pinhole::ngettext('Photo', 'Photos',
							$row->photo_count));
			}
		}

		$y = $date->getYear();
		$m = $date->getMonth();

		return (isset($this->calendar_photo_count_array[$y.'/'.$m])) ?
			$this->calendar_photo_count_array[$y.'/'.$m] : array();
	}

	// }}}
	// {{{ protected function displayNextPrevDate()

	protected function displayNextPrevDate()
	{
		return;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntrySet(
			$this->photo_ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/styles/pinhole-calendar-display.css', Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
