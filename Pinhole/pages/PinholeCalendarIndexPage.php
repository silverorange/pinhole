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

	// }}}

	// init phase
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout,
		$date_parts = null)
	{
		parent::__construct($app, $layout);

		if ($date_parts !== null)
			$this->date_parts = explode('/', $date_parts);
	}

	// }}}
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->photo_ui = new SwatUI();
		$this->photo_ui->mapClassPrefixToPath('Pinhole', 'Pinhole');
		$this->photo_ui->loadFromXML($this->photo_ui_xml);

		/* Set YUI Grid CSS class for a 300px wide left column */
		$this->layout->data->yui_grid_class = 'yui-t3';

		$this->setDateRange();
	}

	// }}}
	// {{{ protected function setDateRange()

	protected function setDateRange()
	{
		$this->start_date = new SwatDate();
		$this->start_date->setYear($this->date_parts[0]);
		$this->start_date->setMonth(isset($this->date_parts[1]) ? $this->date_parts[1] : 1);
		$this->start_date->setDay(isset($this->date_parts[2]) ? $this->date_parts[2] : 1);
		$this->start_date->setHour(0);
		$this->start_date->setMinute(0);
		$this->start_date->setSecond(0);

		$this->end_date = clone $this->start_date;

		if (isset($this->date_parts[3]) && $this->date_parts[3] == 'week') {
			$this->end_date->addSeconds(7 * 86400);
			$this->layout->data->title = sprintf('Week of %s',
				$this->start_date->format(SwatDate::DF_DATE_LONG));
		} elseif (isset($this->date_parts[2])) {
			$this->end_date->addSeconds(86400);
			$this->layout->data->title =
				$this->start_date->format(SwatDate::DF_DATE_LONG);
		} elseif (isset($this->date_parts[1])) {
			$this->end_date->setMonth($this->start_date->getMonth() == 12
				? 1 : $this->start_date->getMonth() + 1);
			$this->layout->data->title =
				$this->start_date->format(SwatDate::DF_MY);
		} else {
			$this->end_date->setYear($this->start_date->getYear() + 1);
			$this->layout->data->title =
				$this->start_date->format('%Y');
		}
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
		$this->displayCalendars();
		$this->layout->endCapture();

		$view = $this->photo_ui->getWidget('photo_view');
		$view->model = $this->getPhotoTableStore(20);

		$pagination = $this->photo_ui->getWidget('pagination');
		$pagination->total_records = $this->getPhotoCount();
		$pagination->page_size = 20;
		$pagination->link = 'photo/%s';

		$this->layout->startCapture('content');
		$this->photo_ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function getPhotoTableStore()

	protected function getPhotoTableStore($limit = null, $offset = null)
	{
		$photos = PinholePhotoWrapper::loadSetFromDBWithDimension(
			$this->app->db, 'thumb', $this->getDateWhereClause(),
			$limit, $offset);

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
			where PinholePhoto.status = %s and %s',
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer'),
			$this->getDateWhereClause());

		return SwatDB::queryOne($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function getDateWhereClause()

	protected function getDateWhereClause()
	{
		return sprintf(' PinholePhoto.photo_date >= %s
			and PinholePhoto.photo_date < %s',
			$this->app->db->quote($this->start_date->getDate(), 'date'),
			$this->app->db->quote($this->end_date->getDate(), 'date'));
	}

	// }}}
	// {{{ protected function displayCalendars()

	protected function displayCalendars()
	{
		$date = new SwatDate();

		for ($i = 1; $i <= 12; $i++) {
			$month = $date->getMonth();
			$year = $date->getYear();

			$cal = new PinholeCalendarDisplay('cal'.$i);
			$cal->setMonth($date);

			$cal->setSelectedDateRange($this->start_date, $this->end_date);
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

		$this->layout->addHtmlHeadEntrySet(
			$this->photo_ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/styles/pinhole-calendar-display.css', Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
