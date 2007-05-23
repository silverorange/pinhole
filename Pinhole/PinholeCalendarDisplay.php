<?php

require_once 'Swat/SwatControl.php';
require_once 'Date.php';

/**
 * Javascript display calendar widget
 *
 * This widget uses JavaScript to display a calendar.
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeCalendarDisplay extends SwatControl
{
	// {{{ constants

	const RANGE_MONTH = 1;
	const RANGE_WEEK = 2;
	const RANGE_DAY = 3;

	// }}}
	// {{{ public properties

	public $link = '%s';

	// }}}
	// {{{ private properties

	/**
	 * Date of the month to display (day is ignored)
	 *
	 * @var Date
	 */
	private $date;
	private $selected_range;
	private $selected_range_start;
	private $selected_range_end;
	private $selected_range_class;

	private $date_titles = array();
	private $date_classes = array();

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new calendar
	 *
	 * @param string $id a non-visible unique id for this widget.
	 *
	 * @see PinholeWidget::__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->requires_id = false;
	}

	// }}}
	// {{{ public function setMonth()

	public function setMonth(Date $date)
	{
		$this->date = $date;
		$this->date->setDay(1);
		$this->date->clearTime();
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this calendar widget
	 */
	public function display()
	{
		if (!$this->visible)
			return;

		$table = new SwatHtmlTag('table');
		$table->class = 'pinhole-calendar';
		$caption = new SwatHtmlTag('caption');
		$thead = new SwatHtmlTag('thead');
		$tbody = new SwatHtmlTag('tbody');
		$tr = new SwatHtmlTag('tr');
		$th = new SwatHtmlTag('th');
		$td = new SwatHtmlTag('td');
		$a = new SwatHtmlTag('a');

		$table->class.= ($this->selected_range == self::RANGE_MONTH) ?
			' '.$this->selected_range_class : '';
		$table->open();
		$caption->open();
			$this->displayMonthTitle();
		$caption->close();

		$thead->open();
			$this->displayWeekdayHeader();
		$thead->close();

		$tbody->open();

		$end_day = $this->date->getDaysInMonth();
		$start_day = $this->date->getDayOfWeek();
		$day = clone $this->date;
		$day->subtractSeconds(86400 * ($start_day));

		$count = 0;

		$total_rows = ceil(($start_day + $end_day - 1) / 7);

		for ($row = 0; $row < $total_rows; $row++) {
			$tr->class = ($this->selected_range == self::RANGE_WEEK &&
				$count - $start_day + 1 == $this->selected_range_start->getDay()) ?
				$this->selected_range_class : null;

			$tr->open();

			$td->open();
			$a->href = $this->getLink($day, 'week'); 
			$a->title = sprintf(Pinhole::_('Week of %s'),
				$day->format(SwatDate::DF_DATE));
			$a->setContent('»');
			$a->display();
			$td->close();

			for ($col = 0; $col < 7; $col++) {
				if ($count >= $start_day && $count < ($start_day + $end_day)) {
					$a->title = $this->getTitle($day);
					$a->href = $this->getLink($day, 'date'); 
					$a->setContent($day->getDay());

					$td->class = $this->getClassName($day);
					$td->open();
					$a->display();
					$td->close();
					$td->class = null;
					$a->title = null;
				} else {
					$td->setContent('&nbsp;', 'text/xml');
					$td->display();
				}

				$day->addSeconds(86400);
				$count++;
			}

			$tr->close();
		}

		$tbody->close();
		$table->close();
	}

	// }}}
	// {{{ public function addClassName()

	/**
	 * Add a CSS class to specific dates
	 *
	 * @param string $class_name The name of the css class.
	 * @param array $days An array of days to highlight.
	 */
	public function addClassName($class_name, $days)
	{
		$this->date_classes[$class_name] = $days;
	}

	// }}}
	// {{{ public function setHighlightedDays()

	/**
	 * Set the specified days as highlighted
	 *
	 * @param array $days An associative array of days and optional titles
	 * 	for the highlighted days.
	 * @param string $class_name The name of the css class.
	 */
	public function setHighlightedDays($days, $class_name = 'highlight')
	{
		$this->date_titles = $days;
		$this->addClassName($class_name, array_keys($days));
	}

	// }}}
	// {{{ public function setSelectedDateRange()

	/**
	 * Set the selected date range
	 *
	 * @param Date $range_start
	 * @param Date $range_end
	 */
	public function setSelectedDateRange($range_start, $range_end,
		$class_name = 'selected')
	{
		$this->selected_range_start = $range_start;
		$this->selected_range_end = $range_end;
		$this->selected_range_class = $class_name;

		$end_date = clone $this->date;
		$end_date->addSeconds($this->date->getDaysInMonth() * 86400);

		if (Date::compare($range_start, $this->date) <= 0 &&
			Date::compare($range_end, $end_date) >= 0) {
			$this->selected_range = self::RANGE_MONTH;
		} elseif (Date::compare($range_start, $this->date) >= 0 &&
			Date::compare($range_end, $end_date) <= 0) {

			$next_week = clone $range_start;
			$next_week->addSeconds(7 * 86400);

			if ($range_start->getDayOfWeek() == 1 &&
				$range_end->equals($next_week)) {
				$this->selected_range = self::RANGE_WEEK;
			} else {

				if (Date::compare($range_start, $this->date) < 0)
					$start_day = 1;
				else
					$start_day = $range_start->getDay();

				if (Date::compare($range_end, $end_date) > 0)
					$end_day = $this->date->getDaysInMonth();
				else
					$end_day = $range_end->getDay();

				$days = array();
				for ($i = $start_day; $i < $end_day; $i++)
					$days[] = $i;

				$this->addClassName($this->selected_range_class, $days);
				$this->selected_range = self::RANGE_DAY;
			}
		}
	}

	// }}}
	// {{{ protected function displayMonthTitle()

	protected function displayMonthTitle()
	{
		$a_tag = new SwatHtmlTag('a');

		$a_tag->href = $this->getLink($this->date, 'month'); 
		$a_tag->setContent($this->date->format('%B %Y'));
		$a_tag->display();
	}

	// }}}
	// {{{ protected function displayWeekdayHeader()

	protected function displayWeekdayHeader()
	{
		$tr_tag = new SwatHtmlTag('tr');
		$th_tag = new SwatHtmlTag('th');

		/*
		 * This date is arbitrary and is just used for getting week names.
		 */
		$date = new Date();
		$date->setDay(1);
		$date->setMonth(1);
		$date->setYear(1995);

		$tr_tag->open();

		$th_tag->display();

		for ($i = 1; $i < 8; $i++) {
			$date_string = $date->format('%a');
			$th_tag->setContent(substr($date_string, 0, 1));
			$th_tag->display();
			$date->setDay($i + 1);
		}

		$tr_tag->close();
	}

	// }}}
	// {{{ private function getClassName()

	/**
	 */
	private function getClassName($date)
	{
		$classes = array();
		$key = $date->format('%Y-%m-%d');

		foreach ($this->date_classes as $class => $days)
			if (in_array($key, $days))
				$classes[] = $class;

		return (count($classes) > 0) ? implode(' ', $classes) : null;
	}

	// }}}
	// {{{ private function getLink()

	/**
	 */
	private function getLink($date, $type = 'date')
	{
		$link = null;

		switch ($type) {
		case 'date' :
		case 'week' :
			$link = sprintf('date.%s=%s',
				$type,
				$date->format('%Y-%m-%d'));
			break;
		case 'month' :
			$link = sprintf('date.month=%s/date.year=%s',
				intval($date->format('%m')),
				$date->format('%Y'));
			break;
		case 'year' :
			$link = sprintf('date.year=%s',
				$date->format('%Y'));
			break;
		}

		return ($link === null) ? null : sprintf($this->link, $link);
	}

	// }}}
	// {{{ private function getTitle()

	/**
	 */
	private function getTitle($date)
	{
		$key = $date->format('%Y-%m-%d');

		if (isset($this->date_titles[$key]))
			return sprintf('%d %s',
				$this->date_titles[$key],
				Pinhole::ngettext('Photo', 'Photos',
					$this->date_titles[$key]));
		else
			return null;
	}

	// }}}
}

?>
