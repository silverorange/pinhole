<?php

require_once 'Swat/SwatControl.php';
require_once 'Pinhole/PinholeTagList.php';
require_once 'MDB2.php';

/**
 * A control to browse photos by date tags
 *
 * Given a tag list, this control displays tags for the applicable years,
 * months and days of the photo set belonging to the tag list.
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholeDateTag
 */
class PinholeDateTagBrowser extends SwatControl
{
	// {{{ public properties

	/**
	 * The URI component relative the base URI used for displaying links
	 *
	 * This value will be prepended to links displayed by this date tag
	 * browser.
	 *
	 * @var string
	 */
	public $base = 'tag';

	// }}}
	// {{{ protected properties

	/**
	 * The tag list of this date tag browser
	 *
	 * @var PinholeTagList
	 *
	 * @see PinholeDateTagBrowser::setTagList()
	 */
	protected $tag_list;

	/**
	 * The database connection used by this date tag browser
	 *
	 * @var MDB2_Driver_Common
	 *
	 * @see PinholeDateTagBrowser::setDatabase()
	 */
	protected $db;

	/**
	 * The optional memcache module used by this date tag browser
	 *
	 * @var SiteMemcacheModule
	 */
	protected $memcache;

	// }}}
	// {{{ public function display()

	/**
	 * Displays this date tag browser
	 */
	public function display()
	{
		if (!$this->visible)
			return;

		if ($this->tag_list === null)
			return;

		parent::display();

		$date_range = $this->tag_list->getPhotoInfo();
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

	// }}}
	// {{{ public function setTagList()

	/**
	 * Sets the tag list used by this date tag browser
	 *
	 * @param PinholeTagList $tag_list the tag list to use for this date tag
	 *                                  browser.
	 */
	public function setTagList(PinholeTagList $tag_list)
	{
		$this->tag_list = $tag_list;
	}

	// }}}
	// {{{ public function setDatabase()

	/**
	 * Sets the database connection used by this date tag browser
	 *
	 * @param MDB2_Driver_Common $db the database connection to use for this
	 *                                date tag browser.
	 */
	public function setDatabase(MDB2_Driver_Common $db)
	{
		$this->db = $db;
	}

	// }}}
	// {{{ public function setCache()

	/**
	 * Optionally specify a memcache module
	 *
	 * @param SiteMemcacheModule $memcache the memcache module for this data
	 *                                  browser
	 */
	public function setCache(SiteMemcacheModule $memcache)
	{
		$this->memcache = $memcache;
	}

	// }}}
	// {{{ protected function displayYears()

	/**
	 * Displays date.year tags for all the photos in the site instance of
	 * this date tag browser's tag list
	 *
	 * The <i>$start_date</i> and <i>$end_date</i> are used to tell which
	 * year is currently selected.
	 *
	 * @param SwatDate $start_date the start date of the photos in this date
	 *                              tag browser's tag list.
	 * @param SwatDate $end_date the end date of the photos in this date
	 *                              tag browser's tag list.
	 */
	protected function displayYears(SwatDate $start_date, SwatDate $end_date)
	{
		// create a new tag list with the same filters as the current tag
		// list but containing no tags
		$empty_tag_list = $this->tag_list->getEmptyCopy();

		// get date range of all photos with current tag list's filters
		$date_range = $empty_tag_list->getPhotoInfo();

		// if there are no photos, don't display years
		if ($date_range['start'] === null &&
			$date_range['end'] === null)
			return;

		$year_start = $date_range['start']->getYear();
		$year_end   = $date_range['end']->getYear();
		$date       = new SwatDate();

		// get selected year if it exists
		if ($start_date->getYear() == $end_date->getYear())
			$selected_year = $start_date->getYear();
		else
			$selected_year = null;

		// create base tag list that new date.year tags will be added to
		$tag_list = $this->tag_list->filter(array('PinholeDateTag'));
		$photos = $tag_list->getPhotoCountByDate('year');

		// display date.year tags for each year in global date range
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'years clearfix';
		$div_tag->open();

		for ($i = $year_start; $i <= $year_end; $i++) {
			$date->setYear($i);

			$tag_string = sprintf('date.year=%s',
				$date->formatLikeIntl('yyyy'));

			$tag_list->add($tag_string);

			if ($selected_year === $i) {
				$span_tag = new SwatHtmlTag('span');
				$span_tag->class = 'selected';
				$span_tag->setContent($date->formatLikeIntl('yyyy'));
				$span_tag->display();
			} elseif (array_key_exists($i, $photos)) {
				$a_tag = new SwatHtmlTag('a');
				$a_tag->href = $this->base.'?'.$tag_list->__toString();
				$a_tag->title = sprintf(Pinhole::ngettext(
					'1 photo', '%s photos', $photos[$i]),
					SwatString::numberFormat($photos[$i]));

				$a_tag->setContent($date->formatLikeIntl('yyyy'));
				$a_tag->display();
			} else {
				$span_tag = new SwatHtmlTag('span');
				$span_tag->setContent($date->formatLikeIntl('yyyy'));
				$span_tag->display();
			}

			$tag_list->remove($tag_string);
		}

		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayMonths()

	/**
	 * Displays date.month tags for all the photos in the site instance of
	 * this date tag browser's tag list
	 *
	 * The <i>$start_date</i> and <i>$end_date</i> are used to tell which
	 * month and year are currently selected.
	 *
	 * @param SwatDate $start_date the start date of the photos in this date
	 *                              tag browser's tag list.
	 * @param SwatDate $end_date the end date of the photos in this date
	 *                              tag browser's tag list.
	 */
	protected function displayMonths(SwatDate $start_date, SwatDate $end_date)
	{
		$date = new SwatDate();

		/*
		 * Setting the year to the $start_date year makes sense because the
		 * month list is only displayed if the start year and end year are the
		 * same.
		 */
		$date->setYear($start_date->getYear());

		// get selected month if it exists
		if ($start_date->getMonth() == $end_date->getMonth()) {
			$selected_month = $start_date->getMonth();
		} else {
			$selected_month = null;
		}

		// create base tag list that new date.month tags will be added to
		$tag_list = $this->tag_list->filter(array('PinholeDateTag'));
		$tag_list->add(sprintf('date.year=%s', $date->formatLikeIntl('yyyy')));
		$photos = $tag_list->getPhotoCountByDate('month');

		// display date.month tags for each month
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'months clearfix';
		$div_tag->open();

		for ($i = 1; $i <= 12; $i++) {
			$date->setMonth($i);
			$key = $date->formatLikeIntl('yyyy-MM');

			$tag_string = sprintf('date.month=%s', $date->formatLikeIntl('MM'));
			$tag_list->add($tag_string);

			if ($selected_month === $i) {
				$span_tag = new SwatHtmlTag('span');
				$span_tag->class = 'selected';
				$span_tag->setContent($date->formatLikeIntl('MMMM'));
				$span_tag->display();
			} elseif (array_key_exists($key, $photos)) {
				$a_tag = new SwatHtmlTag('a');
				$a_tag->href = $this->base.'?'.$tag_list->__toString();
				$a_tag->title = sprintf(Pinhole::ngettext(
					'1 photo', '%s photos', $photos[$key]),
					SwatString::numberFormat($photos[$key]));

				$a_tag->setContent($date->formatLikeIntl('MMMM'));
				$a_tag->display();
			} else {
				$span_tag = new SwatHtmlTag('span');
				$span_tag->setContent($date->formatLikeIntl('MMMM'));
				$span_tag->display();
			}

			$tag_list->remove($tag_string);
		}

		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayDays()

	/**
	 * Displays date.date tags for all the photos in the site instance of
	 * this date tag browser's tag list
	 *
	 * The <i>$start_date</i> and <i>$end_date</i> are used to tell which
	 * day, month and year are currently selected.
	 *
	 * @param SwatDate $start_date the start date of the photos in this date
	 *                              tag browser's tag list.
	 * @param SwatDate $end_date the end date of the photos in this date
	 *                              tag browser's tag list.
	 */
	protected function displayDays(SwatDate $start_date, SwatDate $end_date)
	{
		$date = new SwatDate();

		/*
		 * Setting the month and year to the $start_date month and year makes
		 * sense because the day list is only displayed if the start year and
		 * month are the same as the end year and month.
		 */
		$date->setMonth($start_date->getMonth());
		$date->setYear($start_date->getYear());

		// get selected day if it exists
		if ($start_date->getDay() == $end_date->getDay()) {
			$selected_day = $start_date->getDay();
		} else {
			$selected_day = null;
		}

		// create base tag list that new date.month tags will be added to
		$tag_list = $this->tag_list->filter(array('PinholeDateTag'));
		$tag_list->add(sprintf('date.year=%s', $date->formatLikeIntl('yyyy')));
		$tag_list->add(sprintf('date.month=%s', $date->formatLikeIntl('MM')));
		$photos = $tag_list->getPhotoCountByDate('day');

		// Filter again since the day list uses date.date tags instead of
		// combined date.year, date.month and date.day tags.
		$tag_list = $this->tag_list->filter(array('PinholeDateTag'));

		// display date.date tags for each day
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'days clearfix';
		$div_tag->open();

		$days_in_month = $date->getDaysInMonth();
		for ($i = 1; $i <= $days_in_month; $i++) {
			$date->setDay($i);
			$key = $date->formatLikeIntl('yyyy-MM-dd');

			$tag_string = sprintf('date.date=%s',
				$date->formatLikeIntl('yyyy-MM-dd'));

			$tag_list->add($tag_string);

			if ($selected_day === $i) {
				$span_tag = new SwatHtmlTag('span');
				$span_tag->setContent($date->formatLikeIntl('dd'));
				$span_tag->class = 'selected';
				$span_tag->display();
			} elseif (array_key_exists($key, $photos)) {
				$a_tag = new SwatHtmlTag('a');
				$a_tag->href = $this->base.'?'.$tag_list->__toString();
				$a_tag->title = sprintf(Pinhole::ngettext(
					'1 photo', '%s photos', $photos[$key]),
					SwatString::numberFormat($photos[$key]));

				$a_tag->setContent($date->formatLikeIntl('dd'));
				$a_tag->display();
			} else {
				$span_tag = new SwatHtmlTag('span');
				$span_tag->setContent($date->formatLikeIntl('dd'));
				$span_tag->display();
			}

			$tag_list->remove($tag_string);
		}

		$div_tag->close();
	}

	// }}}
}

?>
