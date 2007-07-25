<?php

require_once 'Swat/SwatControl.php';
require_once 'Pinhole/PinholeTagList.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeDateTagBrowser extends SwatControl
{
	public $base = 'tag';

	protected $tag_list;

	protected $db;

	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addStyleSheet(
			'packages/pinhole/styles/pinhole-date-tag-browser.css',
			Pinhole::PACKAGE_ID);
	}

	public function display()
	{
		if (!$this->visible)
			return;

		if ($this->tag_list === null)
			return;

		$date_range = $this->tag_list->getPhotoDateRange();
		$start_date = $date_range['start'];
		$end_date   = $date_range['end'];

		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = $this->id;
		$div_tag->class = 'pinhole-date-tag-browser';
		$div_tag->open();

		// always show year selector
		$this->displayYears($start_date, $end_date);

		// if all photos in the tag list are in the same year, show the month
		// selector
		if ($start_date->getYear() == $end_date->getYear()) {
			$this->displayMonths($start_date, $end_date);

			// if all photos in the tag list are in the same month, show the
			// day selector
			if ($start_date->getMonth() == $end_date->getMonth()) {
				$this->displayDays($start_date, $end_date);
			}
		}

		$div_tag->close();
	}

	public function setTagList(PinholeTagList $tag_list)
	{
		$this->tag_list = $tag_list;
	}

	public function setDatabase(MDB2_Driver_Common $db)
	{
		$this->db = $db;
	}

	protected function displayYears(SwatDate $start_date, SwatDate $end_date)
	{
		// create a new tag list with the same filters as the current tag
		// list but containing no tags
		$empty_tag_list =
			$this->tag_list->intersect(new PinholeTagList($this->db));

		// get date range of all photos with current tag list's filters
		$global_date_range = $empty_tag_list->getPhotoDateRange();

		// if there are no photos, don't display years
		if ($global_date_range['start'] === null &&
			$global_date_range['end'] === null)
			return;

		$year_start = $global_date_range['start']->getYear();
		$year_end   = $global_date_range['end']->getYear();
		$date       = new SwatDate();

		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'years clearfix';
		$div_tag->open();

		$tag_list = $this->tag_list->filter(array('PinholeDateTag'));

		$photos = $this->getPhotoCountByDate('year');
		for ($i = $year_start; $i <= $year_end; $i++) {
			$date->setYear($i);

			$tag_string = sprintf('date.year=%s', $date->format('%Y'));
			$tag_list->add($tag_string);

			if (array_key_exists($i, $photos)) {
				$a_tag = new SwatHtmlTag('a');

				$a_tag->title = sprintf(Pinhole::ngettext(
					'one photo', '%s photos', $photos[$i]),
					SwatString::numberFormat($photos[$i]));

				if ($start_date->getYear() == $end_date->getYear() &&
					$start_date->getYear() == $i) {
					$a_tag->class = 'selected';
				}

				$a_tag->href = 'tag/'.$tag_list->__toString();

				$a_tag->setContent($date->format('%Y'));
				$a_tag->display();
			} else {
				$span_tag = new SwatHtmlTag('span');
				$span_tag->setContent($date->format('%Y'));
				$span_tag->display();
			}

			$tag_list->remove($tag_string);
		}

		$div_tag->close();
	}

	protected function displayMonths(SwatDate $start_date, SwatDate $end_date)
	{

		$date = new SwatDate();
		$date->setYear($start_date->getYear());

		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'months clearfix';
		$div_tag->open();

		$tag_list = $this->tag_list->filter(array('PinholeDateTag'));
		$tag_list->add(sprintf('date.year=%s', $date->format('%Y')));

		$photos = $this->getPhotoCountByDate('month');
		for ($i = 1; $i <= 12; $i++) {
			$date->setMonth($i);
			$key = $date->format('%Y-%m');

			$tag_string = sprintf('date.month=%s', $date->format('%m'));
			$tag_list->add($tag_string);

			if (array_key_exists($key, $photos)) {
				$a_tag = new SwatHtmlTag('a');
				$a_tag->title = sprintf(Pinhole::ngettext(
					'one photo', '%s photos', $photos[$key]),
					SwatString::numberFormat($photos[$key]));

				if ($start_date->getMonth() == $end_date->getMonth() &&
					$start_date->getMonth() == $i) {
					$a_tag->class = 'selected';
				}

				$a_tag->href = 'tag/'.$tag_list->__toString();

				$a_tag->setContent($date->format('%B'));
				$a_tag->display();
			} else {
				$span_tag = new SwatHtmlTag('span');
				$span_tag->setContent($date->format('%B'));
				$span_tag->display();
			}

			$tag_list->remove($tag_string);
		}

		$div_tag->close();
	}

	protected function displayDays(SwatDate $start_date, SwatDate $end_date)
	{

		$date = new SwatDate();
		$date->setMonth($start_date->getMonth());
		$date->setYear($end_date->getYear());

		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'days clearfix';
		$div_tag->open();

		$tag_list = $this->tag_list->filter(array('PinholeDateTag'));

		$photos = $this->getPhotoCountByDate('day');
		for ($i = 1; $i <= $date->getDaysInMonth(); $i++) {
			$date->setDay($i);
			$key = $date->format('%Y-%m-%d');

			$tag_string = sprintf('date.date=%s', $date->format('%Y-%m-%d'));
			$tag_list->add($tag_string);

			if (array_key_exists($key, $photos)) {
				$a_tag = new SwatHtmlTag('a');
				$a_tag->title = sprintf(Pinhole::ngettext(
					'one photo', '%s photos', $photos[$key]),
					SwatString::numberFormat($photos[$key]));

				if ($start_date->getDay() == $end_date->getDay() &&
					$start_date->getDay() == $i) {
					$a_tag->class = 'selected';
				}

				$a_tag->href = 'tag/'.$tag_list->__toString();

				$a_tag->setContent($date->format('%d'));
				$a_tag->display();
			} else {
				$span_tag = new SwatHtmlTag('span');
				$span_tag->setContent($date->format('%d'));
				$span_tag->display();
			}

			$tag_list->remove($tag_string);
		}

		$div_tag->close();
	}

	protected function getPhotoCountByDate($date_part = 'day')
	{
		$group_by_parts = array();

		switch ($date_part) {
		case 'day' :
			$group_by_parts[] = 'day';
			$group_by_parts[] = 'month';
			$group_by_parts[] = 'year';
			$date_format = '%Y-%m-%d';
			break;

		case 'month' :
			$group_by_parts[] = 'month';
			$group_by_parts[] = 'year';
			$date_format = '%Y-%m';
			break;

		case 'year' :
			$group_by_parts[] = 'year';
			$date_format = '%Y';
			break;
		}

		$group_by_clause = '';

		$count = 0;
		foreach ($group_by_parts as $part) {
			if ($count > 0)
				$group_by_clause.= ', ';

			$group_by_clause.= sprintf(
				'date_part(%s, PinholePhoto.photo_date)',
				$this->db->quote($part, 'text'));

			$count++;
		}

		$sql = 'select
				count(PinholePhoto.id) as photo_count,
				max(PinholePhoto.photo_date) as photo_date
			from PinholePhoto';

		$join_clauses = implode(' ', $this->tag_list->getJoinClauses());
		if (strlen($join_clauses) > 0)
			$sql.= ' '.$join_clauses.' ';

		$where_clause = $this->tag_list->getWhereClause();
		if (strlen($where_clause) > 0)
			$sql.= ' where '.$where_clause;

		if (strlen($group_by_clause) > 0)
			$sql.= ' group by '.$group_by_clause;

		$rows = SwatDB::query($this->db, $sql, null);

		$dates = array();
		while ($row = $rows->fetchRow(MDB2_FETCHMODE_OBJECT)) {
			//foreach ($rows as $row) {
			$date = new SwatDate($row->photo_date);
			$dates[$date->format($date_format)] = $row->photo_count;
		}

		return $dates;
	}
}

?>
