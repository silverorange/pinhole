<?php

require_once 'Date/Calc.php';
require_once 'Swat/SwatDate.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/tags/PinholeAbstractMachineTag.php';
require_once 'Pinhole/tags/PinholeIterableTag.php';

/**
 * Machine tag for dates
 *
 * This machine tag has the namespace 'date' and the following names:
 *
 * - <i>date</i>:  represents a specific day with values of YYYY-MM-DD.
 * - <i>week</i>:  represents a specific week with either a numeric value from
 *                 1 to 52 or a day value of YYY-MM-DD.
 * - <i>year</i>:  represents a specific year with a numeric value.
 * - <i>month</i>: represents a specific month with a numeric value from
 *                 1 to 12.
 * - <i>day</i>:   represents a specific day with a numeric value from 1 to 31.
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeDateTag extends PinholeAbstractMachineTag
	implements PinholeIterableTag
{
	// {{{ class constants

	/**
	 * The namespace of the date machine tag
	 */
	const NAMESPACE = 'date';

	// }}}
	// {{{ private propeties

	/**
	 * Name of this date tag
	 *
	 * Should be one of 'date', 'week', 'year', 'month', or 'day'.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Value of this date tag
	 *
	 * @var string
	 */
	private $value;

	// }}}
	// {{{ public function parse()

	/**
	 * Parses this date tag from a tag string
	 *
	 * @param string $string the tag string to parse. 
	 * @param MDB2_Driver_Common $db the database connection used to parse the
	 *                            tag string.
	 *
	 * @return boolean true if the tag string could be parsed and false if the
	 *                  tag string could not be parsed.
	 */
	public function parse($string, MDB2_Driver_Common $db)
	{
		$this->setDatabase($db);

		$parts = $this->getParts($string);
		if (count($parts) > 0 &&
			$this->isValid($parts['name'], $parts['value'])) {

			$this->name =  $parts['name'];
			$this->value = $parts['value'];

			$valid = true;
		} else {
			$valid = false;
		}

		return $valid;
	}

	// }}}
	// {{{ public function getTitle()

	/**
	 * Gets the title of this date tag
	 *
	 * @return string the title of this date tag.
	 */
	public function getTitle()
	{
		switch ($this->name) {
		case 'date':
			$date = new SwatDate($this->value);
			$title = $date->format(SwatDate::DF_DATE);
			break;

		case 'week':
			if (ctype_digit($this->value)) {
				// get date by week number
				$span = new Date_Span();
				$span->setFromDays(($this->value - 1) * 7);
				$start_date = new SwatDate();
				$start_date->setMonth(1);
				$start_date->setDay(1);
				$start_date->addSpan($span);
			} else {
				$date = new SwatDate($this->value);
				$start_date = new SwatDate(Date_Calc::beginOfWeek(
					$date->getDay(), $date->getMonth(), $date->getYear()));
			}
			$title = sprintf(Pinhole::_('Week of %s'),
				$start_date->format(SwatDate::DF_DATE));

			break;

		case 'year':
			$date = new SwatDate();
			$date->setYear($this->value);
			$title = $date->format('%Y');
			break;

		case 'month':
			$date = new SwatDate();
			$date->setMonth($this->value);
			$title = $date->format('%B');
			break;

		case 'day':
			$date = new SwatDate();
			$date->setDay($this->value);
			$title = $date->format('%d');
			break;

		default:
			$title = Pinhole::_('Unknown Date');
			break;
		}

		return $title;
	}

	// }}}
	// {{{ public function getWhereClause()

	/**
	 * Gets the SQL where clause for this date tag
	 *
	 * @return string the SQL where clause for this date tag.
	 */
	public function getWhereClause()
	{
		switch ($this->name) {
		case 'date':
			$date = new SwatDate($this->value);

			// database content is always UTC
			$date->clearTime();
			$date->toUTC();

			$where = sprintf('PinholePhoto.photo_date = %s',
				$this->db->quote($date, 'date'));

			break;

		case 'week':
			if (ctype_digit($this->value)) {
				// get date by week number
				$span = new Date_Span();
				$span->setFromDays(($this->value - 1) * 7);
				$start_date = new SwatDate();
				$start_date->setMonth(1);
				$start_date->setDay(1);
				$start_date->addSpan($span);
				$end_date = new SwatDate(Date_Calc::beginOfNextWeek(
					$start_date->getDay(), $start_date->getMonth(),
					$start_date->getYear()));
			} else {
				$date = new SwatDate($this->value);
				$start_date = new SwatDate(Date_Calc::beginOfWeek(
					$date->getDay(), $date->getMonth(), $date->getYear()));

				$end_date = new SwatDate(Date_Calc::beginOfNextWeek(
					$date->getDay(), $date->getMonth(), $date->getYear()));
			}

			// database content is always UTC
			$start_date->clearTime();
			$end_date->clearTime();
			$start_date->toUTC();
			$end_date->toUTC();

			$where = sprintf('PinholePhoto.photo_date >= %s
				and PinholePhoto.photo_date < %s',
				$this->db->quote($start_date, 'date'),
				$this->db->quote($end_date, 'date'));

			break;

		case 'year':
		case 'month':
		case 'day':
			$where = sprintf('date_part(%s, PinholePhoto.photo_date) = %s',
				$this->db->quote($this->name, 'text'),
				$this->db->quote($this->value, 'date'));

			break;

		default:
			$where = '1 = 1';
			break;
		}

		return $where;
	}

	// }}}
	// {{{ public function applyToPhoto()

	/**
	 * Applies this tag to a photo
	 *
	 * Since date tags cannot be applied to photos, this method does nothing.
	 *
	 * @param PinholePhoto $photo the photo this tag is to be applied to.
	 */
	public function applyToPhoto(PinholePhoto $photo)
	{
		// do nothing since date tags cannot be applied to photos
	}

	// }}}
	// {{{ public function appliesToPhoto()

	/**
	 * Checks whether or not this date tag applies to a given photo
	 *
	 * @param PinholePhoto the photo to check.
	 *
	 * @return boolean true if this tag applies to the given photo and false if
	 *                  this tag does not apply to the given photo.
	 */
	public function appliesToPhoto(PinholePhoto $photo)
	{
		$applies = false;

		switch ($this->name) {
		case 'date':
			$date = new SwatDate($this->value);

			// database content is always UTC
			$date->clearTime();
			$date->toUTC();

			$applies = (Date::compare($photo->photo_date, $date) == 0);
			break;

		case 'week':
			if (ctype_digit($this->value)) {
				// get date by week number
				$span = new Date_Span();
				$span->setFromDays(($this->value - 1) * 7);
				$start_date = new SwatDate();
				$start_date->setMonth(1);
				$start_date->setDay(1);
				$start_date->addSpan($span);
				$end_date = new SwatDate(Date_Calc::beginOfNextWeek(
					$start_date->getDay(), $start_date->getMonth(),
					$start_date->getYear()));
			} else {
				$date = new SwatDate($this->value);
				$start_date = new SwatDate(Date_Calc::beginOfWeek(
					$date->getDay(), $date->getMonth(), $date->getYear()));

				$end_date = new SwatDate(Date_Calc::beginOfNextWeek(
					$date->getDay(), $date->getMonth(), $date->getYear()));
			}

			// database content is always UTC
			$start_date->clearTime();
			$end_date->clearTime();
			$start_date->toUTC();
			$end_date->toUTC();

			$applies = ((Date::compare($photo->photo_date, $start_date) >= 0) &&
				(Date::compare($photo->photo_date, $end_date) <= 0));

			break;

		case 'year':
			$applies = ($photo->photo_date->getYear() == $this->value);
			break;

		case 'month':
			$applies = ($photo->photo_date->getMonth() == $this->value);
			break;

		case 'day':
			$applies = ($photo->photo_date->getDay() == $this->value);
			break;
		}

		return $applies;
	}

	// }}}
	// {{{ public function next()

	/**
	 * Gets the next tag after this tag
	 *
	 * @return PinholeDateTag the next tag after this tag or null if there is
	 *                         no next tag.
	 */
	public function next()
	{
		$returned_tag = null;

		switch ($this->name) {
		case 'date':
			$date = new SwatDate($this->value);
			$value = $date->getNextDay()->format('%Y-%m-%d');
			break;

		case 'week':
			if (ctype_digit($this->value)) {
				$value = ($this->value < 52) ? $this->value + 1 : null;
			} else {
				$date = new SwatDate($this->value);
				$start_date = new SwatDate(Date_Calc::beginOfNextWeek(
					$date->getDay(), $date->getMonth(), $date->getYear()));

				$value = $start_date->format('%Y-%m-%d');
			}

			break;

		case 'year':
			$value = $this->value + 1;
			break;

		case 'month':
			$value = ($this->value < 12) ? $this->value + 1 : null;
			break;

		case 'day':
			$value = ($this->value < 31) ? $this->value + 1 : null;
			break;

		default:
			$value = null;
			break;
		}

		if ($value !== null) {
			$string = sprintf('%s.%s=%s', self::NAMESPACE, $this->name, $value);
			$tag = new PinholeDateTag();
			if ($tag->parse($string, $this->db) !== false) {
				$returned_tag = $tag;
			}
		}

		return $returned_tag;
	}

	// }}}
	// {{{ public function prev()

	/**
	 * Gets the previous tag before this tag
	 *
	 * @return PinholeDateTag the previous tag before this tag or null if there
	 *                         is no previous tag.
	 */
	public function prev()
	{
		$returned_tag = null;

		switch ($this->name) {
		case 'date':
			$date = new SwatDate($this->value);
			$value = $date->getPrevDay()->format('%Y-%m-%d');
			break;

		case 'week':
			if (ctype_digit($this->value)) {
				$value = ($this->value > 1) ? $this->value - 1 : null;
			} else {
				$date = new SwatDate($this->value);
				$start_date = new SwatDate(Date_Calc::beginOfPrevWeek(
					$date->getDay(), $date->getMonth(), $date->getYear()));

				$value = $start_date->format('%Y-%m-%d');
			}

			break;

		case 'year':
			$value = ($this->value > 0) ? $this->value - 1 : null;
			break;

		case 'month':
			$value = ($this->value > 1) ? $this->value - 1 : null;
			break;

		case 'day':
			$value = ($this->value > 1) ? $this->value - 1 : null;
			break;

		default:
			$value = null;
			break;
		}

		if ($value !== null) {
			$string = sprintf('%s.%s=%s', self::NAMESPACE, $this->name, $value);
			$tag = new PinholeDateTag();
			if ($tag->parse($string, $this->db) !== false) {
				$returned_tag = $tag;
			}
		}

		return $returned_tag;
	}

	// }}}
	// {{{ protected function getNamespace()

	/**
	 * Gets the namespace of this date tag
	 *
	 * @return string the namespace of this date tag.
	 */
	protected function getNamespace()
	{
		return self::NAMESPACE;
	}

	// }}}
	// {{{ protected function getName()

	/**
	 * Gets the name of this date tag
	 *
	 * @return string the name of this date tag.
	 */
	protected function getName()
	{
		return $this->name;
	}

	// }}}
	// {{{ protected function getValue()

	/**
	 * Gets the value of this date tag
	 *
	 *
	 * @return string the value of this date tag.
	 */
	protected function getValue()
	{
		return $this->value;
	}

	// }}}
	// {{{ private function isValid()

	/**
	 * Whether or not a name-value pair is valid for this date tag
	 *
	 * @param string $name the name.
	 * @param string $value the value.
	 *
	 * @return boolean true if the name-value pair is valid for this date tag
	 *                  and false if the name-value pair is not valid for this
	 *                  date tag.
	 */
	private function isValid($name, $value)
	{
		$iso_date_expression = '/^(\d{4})-?(\d{1,2})-?(\d{1,2})$/';

		switch ($name) {
		case 'date':
			$matches = array();
			if (preg_match($iso_date_expression, $value, $matches) == 1)
				$valid = checkdate($matches[2], $matches[3], $matches[1]);
			else
				$valid = false;

			break;

		case 'week':
			if (ctype_digit($value)) {
				$week = intval($value);
				$valid = ($week >= 1 && $week <= 52);
			} else {
				$matches = array();
				if (preg_match($iso_date_expression, $value, $matches) == 1)
					$valid = checkdate($matches[2], $matches[3], $matches[1]);
				else
					$valid = false;
			}

			break;

		case 'year':
			$year = intval($value);
			$date = new SwatDate();
			$valid = ($year > 0 && $year <= $date->getYear());
			break;

		case 'month':
			$month = intval($value);
			$valid = ($month >= 1 && $month <= 12);
			break;

		case 'day':
			$day = intval($value);
			$valid = ($day >= 1 && $day <= 31);
			break;

		default:
			$valid = false;
			break;
		}

		return $valid;
	}

	// }}}
}

?>
