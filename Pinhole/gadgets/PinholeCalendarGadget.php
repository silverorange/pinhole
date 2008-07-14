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
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$date = new SwatDate();
		$date->setDay(1);
		$date->setMonth(6);
		$date->clearTime();

		$sql = "select count(PinholePhoto.id) as photo_count,
				date_part('day', max(convertTZ(PinholePhoto.photo_date,
					PinholePhoto.photo_time_zone))) as photo_day
			from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where ImageSet.instance %s %s and PinholePhoto.status = %s
				and PinholePhoto.photo_date is not null
				and convertTZ(PinholePhoto.photo_date,
					PinholePhoto.photo_time_zone) >= %s
			group by date_part('day', convertTZ(PinholePhoto.photo_date,
				PinholePhoto.photo_time_zone))";

		$sql = sprintf($sql,
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'),
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED),
			$this->app->db->quote($date->getDate(), 'date'));

		$days = SwatDB::query($this->app->db, $sql);

		$day_count = array();
		foreach ($days as $day) {
			$day_count[$day->photo_day] = $day->photo_count;
		}

		$start = ((-1) * ($date->getDayOfWeek())) + 1;

		$h4 = new SwatHtmlTag('h4');
		$h4->setContent($date->format('%B, %Y'));
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
		for ($i = $start; $i <= $date->getDaysInMonth(); $i++) {
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
						$date->getYear(),
						$date->getMonth(),
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

		/*
		$locale = SwatI18NLocale::get();

		foreach ($years as $year) {
			$date = new SwatDate($year->photo_date);
			$date->getYear();

			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'pinhole-date-browser-gadget-year';
			$div_tag->open();

			$a_tag = new SwatHtmlTag('a');
			$a_tag->href = $this->app->config->pinhole->path.'tag?date.year='.
				$date->getYear();

			$a_tag->setContent($date->getYear());

			$a_tag->display();

			echo ' '.$locale->formatNumber($year->photo_count).' '.
				Pinhole::ngettext('Photo', 'Photos', $year->photo_count);

			$div_tag->close();
		}
		*/
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
