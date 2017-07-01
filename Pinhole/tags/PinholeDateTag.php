<?php

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
	const NS = 'date';

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
	 * @param SiteInstance the site instance used to parse the tag string.
	 *
	 * @return boolean true if the tag string could be parsed and false if the
	 *                  tag string could not be parsed.
	 */
	public function parse(
		$string,
		MDB2_Driver_Common $db,
		SiteInstance $instance = null
	) {
		$this->setDatabase($db);
		$this->setInstance($instance);

		$parts = $this->getParts($string);
		if (count($parts) > 0 &&
			$this->isValid($parts['name'], $parts['value'])) {

			$this->name = $parts['name'];
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
			$title = $date->formatLikeIntl(SwatDate::DF_DATE);
			break;

		case 'week':
			if (ctype_digit($this->value)) {
				// get date by week number
				$days = ($this->value - 1) * 7;
				$start_date = new SwatDate();
				$start_date->setMonth(1);
				$start_date->setDay(1);
				$start_date->addDays($days);
			} else {
				$start_date = new SwatDate($this->value);
				$start_date->subtractDays($start_date->getDayOfWeek());
			}
			$title = sprintf(Pinhole::_('Week of %s'),
				$start_date->formatLikeIntl(SwatDate::DF_DATE));

			break;

		case 'year':
			$date = new SwatDate();
			$date->setYear($this->value);
			$title = $date->formatLikeIntl('yyyy');
			break;

		case 'month':
			$date = new SwatDate();
			$date->setMonth($this->value);
			$title = $date->formatLikeIntl('MMMM');
			break;

		case 'day':
			$date = new SwatDate();
			$date->setDay($this->value);
			$title = $date->formatLikeIntl('dd');
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
			$date = new SwatDate();
			list($year, $month, $day) =
				sscanf($this->value, "%d-%d-%d");
			$date->setDate($year, $month, $day);

			// only matching the date, time and time zone of reference
			// date are irrelevant
			$date->setTime(0, 0, 0);

			// don't compare times
			$where = sprintf(
				'date_trunc(\'day\', convertTZ(PinholePhoto.photo_date,
					PinholePhoto.photo_time_zone)) =
					date_trunc(\'day\', timestamp %s)',
				$this->db->quote($date, 'date'));

			break;

		case 'week':
			if (ctype_digit($this->value)) {
				// get date by week number
				$days = ($this->value - 1) * 7;
				$start_date = new SwatDate();
				$start_date->setMonth(1);
				$start_date->setDay(1);
				$start_date->addDays($days);

				// beginning of next week
				$end_date = clone $start_date;
				$end_date->addDays(7 - $end_date->getDayOfWeek());
			} else {
				// beginning of current week
				$start_date = new SwatDate($this->value);
				$start_date->subtractDays($start_date->getDayOfWeek());

				// beginning of next week
				$end_date = new SwatDate($this->value);
				$end_date->addDays(7 - $end_date->getDayOfWeek());
			}

			// database content is always UTC
			$start_date->setTime(0, 0, 0);
			$end_date->setTime(0, 0, 0);
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
			$where = sprintf('date_part(%s, convertTZ(PinholePhoto.photo_date,
				PinholePhoto.photo_time_zone)) = %s',
				$this->db->quote($this->name, 'text'),
				$this->db->quote($this->value, 'float'));

			break;

		default:
			$where = '';
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
		switch ($this->name) {
		case 'date':
			$date = new SwatDate($this->value);

			// database content is always UTC
			$date->setTime(0, 0, 0);
			$date->toUTC();

			$applies = (SwatDate::compare($photo->photo_date, $date) == 0);
			break;

		case 'week':
			if (ctype_digit($this->value)) {
				// get date by week number
				$days = ($this->value - 1) * 7;
				$start_date = new SwatDate();
				$start_date->setMonth(1);
				$start_date->setDay(1);
				$start_date->addDays($days);

				// beginning of next week
				$end_date = clone $start_date;
				$end_date->addDays(7 - $end_date->getDayOfWeek());
			} else {
				// beginning of week
				$start_date = new SwatDate($this->value);
				$start_date->subtractDays($start_date->getDayOfWeek());

				// beginning of next week
				$end_date = new SwatDate($this->value);
				$end_date->addDays(7 - $end_date->getDayOfWeek());
			}

			// database content is always UTC
			$start_date->setTime(0, 0, 0);
			$end_date->setTime(0, 0, 0);
			$start_date->toUTC();
			$end_date->toUTC();

			$applies = (
				(SwatDate::compare($photo->photo_date, $start_date) >= 0) &&
				(SwatDate::compare($photo->photo_date, $end_date) <= 0));

			break;

		case 'year':
			$local_photo_date = clone $photo->photo_date;
			$local_photo_date->convertTZById($photo->photo_time_zone);
			$applies = ($local_photo_date->getYear() == $this->value);
			break;

		case 'month':
			$local_photo_date = clone $photo->photo_date;
			$local_photo_date->convertTZById($photo->photo_time_zone);
			$applies = ($local_photo_date->getMonth() == $this->value);
			break;

		case 'day':
			$local_photo_date = clone $photo->photo_date;
			$local_photo_date->convertTZById($photo->photo_time_zone);
			$applies = ($local_photo_date->getDay() == $this->value);
			break;

		default:
			$applies = false;
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
			$date->addDays(1);
			$value = $date->formatLikeIntl('yyyy-MM-dd');
			break;

		case 'week':
			if (ctype_digit($this->value)) {
				$value = ($this->value < 52) ? $this->value + 1 : null;
			} else {
				// beginning of next week
				$start_date = new SwatDate($this->value);
				$start_date->addDays(7 - $start_date->getDayOfWeek());

				$value = $start_date->formatLikeIntl('yyyy-MM-dd');
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
			$string = sprintf('%s.%s=%s', self::NS, $this->name, $value);
			$tag = new PinholeDateTag();
			if ($tag->parse($string, $this->db, $this->instance) !== false) {
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
			$date->subtractDays(1);
			$value = $date->formatLikeIntl('yyyy-MM-dd');
			break;

		case 'week':
			if (ctype_digit($this->value)) {
				$value = ($this->value > 1) ? $this->value - 1 : null;
			} else {
				// beginning of previous week
				$start_date = new SwatDate($this->value);
				$start_date->subtractDays(7 + $start_date->getDayOfWeek());

				$value = $start_date->formatLikeIntl('yyyy-MM-dd');
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
			$string = sprintf('%s.%s=%s', self::NS, $this->name, $value);
			$tag = new PinholeDateTag();
			if ($tag->parse($string, $this->db, $this->instance) !== false) {
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
		return self::NS;
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
			$date = new SwatDate();
			$valid = (ctype_digit($value) && $value > 0 &&
				$value <= $date->getYear());

			break;

		case 'month':
			$valid = (ctype_digit($value) && $value >= 1 && $value <= 12);
			break;

		case 'day':
			$valid = (ctype_digit($value) && $value >= 1 && $value <= 31);
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
