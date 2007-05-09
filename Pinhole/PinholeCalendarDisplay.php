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
	// {{{ public properties

	/**
	 * Date of the month to display (day is ignored)
	 *
	 * @var Date
	 */
	public $display_month;

	// }}}
	// {{{ private properties

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
			$this->display_month->format('%Y'),
			intval($this->display_month->format('%m')));

		$a->href = $base_link;
		$a->setContent($this->display_month->format('%B %Y'));
		ob_start();
		$a->display();
		$caption->setContent(ob_get_clean(), 'text/xml');

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

		$end_day = $this->display_month->getDaysInMonth();
		$start_day = $this->display_month->getDayOfWeek();

		$count = 1;

		$total_rows = ceil(($start_day + $end_day - 1) / 7);

		for ($row = 0; $row < $total_rows; $row++) {
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
