<?php

require_once 'Swat/SwatString.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatUI.php';
require_once 'Swat/SwatDate.php';

require_once 'Pinhole/pages/PinholeBrowserIndexPage.php';
require_once 'Pinhole/PinholePhotoCellRenderer.php';
require_once 'Pinhole/PinholeCalendarDisplay.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeCalendarIndexPage extends PinholeBrowserIndexPage
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
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->photo_ui = new SwatUI();
		$this->photo_ui->mapClassPrefixToPath('Pinhole', 'Pinhole');
		$this->photo_ui->loadFromXML($this->photo_ui_xml);

		if ($this->getDisplayYear() === null) {
			$date = new SwatDate();
			$path = $this->tag_intersection->getIntersectingTagPath();
			$this->app->relocate(sprintf('calendar%s/date.year=%s',
				($path == '') ? '' : '/'.$path,
				$date->getYear()));
		}
	}

	// }}}

	// build phase
	// {{{ protected function displayTagList()

	protected function displayTagList()
	{
		$date = new SwatDate();

		$date_range = SwatDB::queryRow($this->app->db,
			sprintf('select max(photo_date) as last_photo_date,
				min(photo_date) as first_photo_date
			from PinholePhoto where status = %s',
			$this->app->db->quote(
				PinholePhoto::STATUS_PUBLISHED, 'integer')));

		$first_date = new SwatDate($date_range->first_photo_date);

		$a_tag = new SwatHtmlTag('a');
		$a_tag->class = 'pinhole-calendar-year';

		while ($date->getYear() >= $first_date->getYear()) {
			if ($date->getYear() == $this->getDisplayYear()) {
				$this->displayCalendar($date);
			} else {
				$a_tag->setContent($date->format('%Y'));
				$a_tag->href = sprintf('calendar/date.year=%s',
					$date->format('%Y'));
				$a_tag->display();
			}

			$date->setYear($date->getYear() - 1);
		}
	}

	// }}}
	// {{{ protected function displayCalendar()

	protected function displayCalendar($date)
	{
		$date->setMonth(1);
		$date->setDay(1);
		$date->clearTime();

		$where_clause = sprintf("status = %s
			and date_part('year', photo_date) = %s",
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer'),
			$this->app->db->quote($date->getYear(), 'integer'));

		$date_array = SwatDB::getOptionArray($this->app->db,
			'PinholePhotoCountByDateView',
			'photo_count', 'photo_date', null, $where_clause);

		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'pinhole-calendar-year';
		$div_tag->open();

		for ($i = 1; $i <= 12; $i++) {
			$month = $date->getMonth();
			$year = $date->getYear();

			$cal = new PinholeCalendarDisplay('cal'.$i);
			$cal->link = 'calendar/%s';
			$cal->setMonth($date);

			//$cal->setSelectedDateRange($this->start_date, $this->end_date);
			$cal->setHighlightedDays($date_array);

			$cal->display();

			$date->setMonth($month + 1);
		}

		$div_tag->close();
	}

	// }}}
	// {{{ protected function getDisplayYear()

	protected function getDisplayYear()
	{
		$tags = $this->tag_intersection->getIntersectingTags('PinholeDateTag');

		foreach ($tags as $tag)
			if ($tag->getYear() !== false)
				return $tag->getYear();
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
