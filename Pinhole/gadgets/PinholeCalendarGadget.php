<?php

require_once 'Site/SiteGadget.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'SwatI18N/SwatI18NLocale.php';

/**
 * Displays a calendar of the last month of photos
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeCalendarGadget extends SiteGadget
{
	// {{{ protected properties

	protected $date;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app,
		SiteGadgetInstance $instance)
	{
		parent::__construct($app, $instance);

		$this->date = new SwatDate();
		$this->date->setDay(1);
		$this->date->clearTime();
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$a_tag = new SwatHtmlTag('a');
		$a_tag->setContent($this->date->format('%B, %Y'));
		$a_tag->href = sprintf('%stag?date.month=%s/date.year=%s',
			$this->app->config->pinhole->path,
			$this->date->getMonth(),
			$this->date->getYear());

		$day_count = $this->getPhotoCountPerDay();
		$h4 = new SwatHtmlTag('h4');
		$h4->setContent((string) $a_tag, 'text/xml');
		$h4->display();

		echo '<table>';

		$wd = new Date();
		$wd->setDay(1);
		$wd->setMonth(1);
		$wd->setYear(1995);

		echo '<tr class="days-of-week">';
		for ($i = 1; $i <= 7; $i++) {
			echo '<td>'.$wd->format('%a').'</td>';
			$wd->setDay($i + 1);
		}
		echo '</tr>';

		$locale = SwatI18NLocale::get();
		$count = 0;
		$start = ((-1) * ($this->date->getDayOfWeek())) + 1;
		for ($i = $start; $i <= $this->date->getDaysInMonth(); $i++) {
			if ($i == $start)
				echo '<tr>';
			elseif ($count % 7 == 0)
				echo '</tr><tr>';

			if ($i > 0) {
				if (array_key_exists($i, $day_count)) {
					printf('<td class="has-photos">'.
						'<a href="%stag?date.date=%s-%s-%s" '.
						'title="%s %s">%s</a></td>',
						$this->app->config->pinhole->path,
						$this->date->getYear(),
						$this->date->getMonth(),
						$i,
						$locale->formatNumber($day_count[$i]),
						Pinhole::ngettext('Photo', 'Photos', $day_count[$i]),
						$i);
				} else {
					echo '<td>'.$i.'</td>';
				}
			} else {
				echo '<td>&nbsp;</td>';
			}

			$count++;
		}

		echo '</tr></table>';
	}

	// }}}
	// {{{ protected function getPhotoCountPerDay()

	protected function getPhotoCountPerDay()
	{
		$sql = "select count(PinholePhoto.id) as photo_count,
				date_part('day', max(convertTZ(PinholePhoto.photo_date,
					PinholePhoto.photo_time_zone))) as photo_day
			from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where ImageSet.instance %s %s and PinholePhoto.status = %s
				and PinholePhoto.photo_date is not null
				and convertTZ(PinholePhoto.photo_date,
					PinholePhoto.photo_time_zone) >= %s
				and convertTZ(PinholePhoto.photo_date,
					PinholePhoto.photo_time_zone) < %s
			group by date_part('day', convertTZ(PinholePhoto.photo_date,
				PinholePhoto.photo_time_zone))";

		$end_date = clone $this->date;
		if ($end_date->getMonth() == 12) {
			$end_date->setMonth(1);
			$end_date->setYear($end_date->getYear() + 1);
		} else {
			$end_date->setMonth($end_date->getMonth() + 1);
		}

		$sql = sprintf($sql,
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'),
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED),
			$this->app->db->quote($this->date->getDate(), 'date'),
			$this->app->db->quote($end_date->getDate(), 'date'));


		$days = SwatDB::query($this->app->db, $sql);

		$day_count = array();
		foreach ($days as $day) {
			$day_count[$day->photo_day] = $day->photo_count;
		}

		return $day_count;
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Pinhole::_('Photo Calendar'));
		$this->defineDescription(Pinhole::_(
			'Displays a calendar of the last month with the photos taken.'));
	}

	// }}}
}

?>
