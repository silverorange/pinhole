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

	public $selected_range_class = 'selected';

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

		$base_link = sprintf('%s/%s',
			$this->date->format('%Y'),
			intval($this->date->format('%m')));

		$a->href = $base_link;
		$a->setContent($this->date->format('%B %Y'));
		ob_start();
		$a->display();
		$caption->setContent(ob_get_clean(), 'text/xml');

		$table->class.= ($this->selected_range == self::RANGE_MONTH) ?
			' '.$this->selected_range_class : '';
		$table->open();
		$caption->display();

		/*
		 * This date is arbitrary and is just used for getting week names.
		 */
		$date = new Date();
		$date->setDay(1);
		$date->setMonth(1);
		$date->setYear(1995);

		$thead->open();
		$tr->open();

		$th->display();

		for ($i = 1; $i < 8; $i++) {
			/* TODO: do this better (display first letter only of weekday */
			$date_string = $date->format('%a');
			$th->setContent($date_string{0});
			$th->display();
			$date->setDay($i + 1);
		}

		$tr->close();
		$thead->close();
		$tbody->open();

		$end_day = $this->date->getDaysInMonth();
		$start_day = $this->date->getDayOfWeek();

		$count = 1;

		$total_rows = ceil(($start_day + $end_day - 1) / 7);

		for ($row = 0; $row < $total_rows; $row++) {
			$tr->class = ($this->selected_range == self::RANGE_WEEK &&
				$count - $start_day + 1 == $this->selected_range_start->getDay()) ?
				$this->selected_range_class : null;

			$tr->open();

			$td->open();
			$a->href = sprintf('%s/%s/week',
				$base_link,
				max($count - $start_day + 1, 1));
			$a->setContent('»');
			$a->display();
			$td->close();

			for ($col = 0; $col < 7; $col++) {
				if ($count >= $start_day && $count < ($start_day + $end_day)) {
					$day = $count - $start_day + 1;

					$a->href = sprintf('%s/%s',
						$base_link,
						$day);
					$a->setContent($day);

					$td->class = $this->getClassName($day);
					$td->open();
					$a->display();
					$td->close();
					$td->class = null;
				} else {
					$td->setContent('&nbsp;', 'text/xml');
					$td->display();
				}

				$count++;
			}

			$tr->close();
		}

		$tbody->close();
		$table->close();
	}

	// }}}
	// {{{ public function setMonth()

	public function setMonth(Date $date)
	{
		$this->date = $date;
		$this->date->setDay(1);
		$this->date->setHour(0);
		$this->date->setMinute(0);
		$this->date->setSecond(0);
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
	// {{{ public function setSelectedDateRange()

	/**
	 * Set the selected date range
	 *
	 * @param Date $range_start
	 * @param Date $range_end
	 */
	public function setSelectedDateRange($range_start, $range_end)
	{
		$this->selected_range_start = $range_start;
		$this->selected_range_end = $range_end;

		$end_date = clone $this->date;
		$end_date->setMonth($this->date->getMonth() == 12 ? 1 :
			$this->date->getMonth() + 1);

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
	// {{{ private function getClassName()

	/**
	 */
	private function getClassName($day)
	{
		$classes = array();

		foreach ($this->date_classes as $class => $days)
			if (in_array($day, $days))
				$classes[] = $class;

		return (count($classes) > 0) ? implode(' ', $classes) : null;
	}

	// }}}
}

?>
