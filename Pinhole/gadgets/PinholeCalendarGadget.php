<?php

require_once 'Site/gadgets/SiteGadget.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'SwatI18N/SwatI18NLocale.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';

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

	protected $calendar_id;

	// }}}
	// {{{ protected function displayTitle()

	public function displayTitle()
	{
		if ($this->hasValue('title')) {
			parent::displayTitle();
		}
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		echo '<div id="'.$this->calendar_id.'" '.
			'class="pinhole-calendar-gadget-container">';

		$date = new SwatDate();
		$date->convertTZById($this->app->config->date->time_zone);
		$date->setDay(1);
		$date->setTime(0, 0, 0);

		echo '<div class="pinhole-calendar-gadget-head">';
		echo '<a class="pinhole-calendar-gadget-prev" '.
			'title="'.Pinhole::_('Previous Month').'" '.
			'id="'.$this->calendar_id.'_prev" href="#">«</a>';

		echo '<div id="'.$this->calendar_id.'_month" class="pinhole-calendar-month">';
		echo self::displayCalendarMonth($this->app, $date);
		echo '</div>';

		echo '<a class="pinhole-calendar-gadget-next" '.
			'title="'.Pinhole::_('Next Month').'" '.
			'id="'.$this->calendar_id.'_next" href="#">»</a>';

		echo '</div>';

		echo '<div id="'.$this->calendar_id.'_body" class="pinhole-calendar-body">';
		echo self::displayCalendarBody($this->app, $date);
		echo '</div>';

		echo '</div>';

		Swat::displayInlineJavaScript($this->getInlineJavaScript($date));
	}

	// }}}
	// {{{ public static function displayCalendarMonth()

	public static function displayCalendarMonth(
		SiteWebApplication $app,
		SwatDate $date
	) {
		$a_tag = new SwatHtmlTag('a');
		$a_tag->setContent($date->formatLikeIntl('MMMM, yyyy'));
		$a_tag->title = sprintf('View photos taken during %s',
			$date->formatLikeIntl('MMMM, yyyy'));

		$a_tag->href = sprintf('%stag?date.month=%s/date.year=%s',
			$app->config->pinhole->path,
			$date->getMonth(),
			$date->getYear());

		$h4 = new SwatHtmlTag('h4');
		$h4->setContent((string)$a_tag, 'text/xml');
		$h4->display();
	}

	// }}}
	// {{{ public static function displayCalendarBody()

	public static function displayCalendarBody(
		SiteWebApplication $app,
		SwatDate $date
	) {
		if (isset($app->memcache)) {
			$cache_key = sprintf('PinholeCalendarGadget.%s.%s.%s',
				'displayCalendarBody',
				$date->getISO8601(),
				$app->session->isLoggedIn() ? 'private' : 'public');

			$body = $app->memcache->getNs('photos', $cache_key);
			if ($body !== false) {
				echo $body;
				return;
			}
		}

		ob_start();

		$day_count = self::getPhotoCountPerDay($app, $date);

		echo '<table>';

		$wd = new SwatDate();
		$wd->setDate(1995, 1, 1);

		echo '<tr class="days-of-week">';
		for ($i = 1; $i <= 7; $i++) {
			echo '<td>'.$wd->formatLikeIntl('EEE').'</td>';
			$wd->setDay($i + 1);
		}
		echo '</tr>';

		$locale = SwatI18NLocale::get();
		$start = ((-1) * ($date->getDayOfWeek())) + 1;

		$current_date = clone $date;

		for ($i = 0; $i <= 41; $i++) {
			$day = $i + $start;

			if ($i == 0)
				echo '<tr>';
			elseif ($i % 7 == 0)
				echo '</tr><tr>';

			if ($day > 0 && $day <= $date->getDaysInMonth()) {
				$current_date->setDay($day);

				if (array_key_exists($day, $day_count)) {
					printf('<td class="has-photos">'.
						'<a href="%stag?date.date=%s" '.
						'title="%s %s">%s</a></td>',
						$app->config->pinhole->path,
						$current_date->formatLikeIntl('yyyy-MM-dd'),
						$locale->formatNumber($day_count[$day]),
						Pinhole::ngettext('Photo', 'Photos', $day_count[$day]),
						$day);
				} else {
					echo '<td>'.$day.'</td>';
				}
			} else {
				echo '<td>&nbsp;</td>';
			}
		}

		echo '</tr></table>';

		$body = ob_get_clean();

		if (isset($app->memcache))
			$app->memcache->setNs('photos', $cache_key, $body);

		echo $body;
	}

	// }}}
	// {{{ public static function getPhotoCountPerDay()

	public static function getPhotoCountPerDay(
		SiteWebApplication $app,
		SwatDate $date
	) {
		if (isset($app->memcache)) {
			$cache_key = sprintf('PinholeCalendarGadget.count.%s.%s',
				$date->formatLikeIntl('yyyy-MM'),
				$app->session->isLoggedIn() ? 'private' : 'public');

			$count = $app->memcache->getNs('photos', $cache_key);
			if ($count !== false)
				return $count;
		}

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
				%s
			group by date_part('day', convertTZ(PinholePhoto.photo_date,
				PinholePhoto.photo_time_zone))";

		$end_date = clone $date;
		$end_date->addMonths(1);

		if (!$app->session->isLoggedIn()) {
			$private_where_clause = sprintf('and PinholePhoto.private = %s',
				$app->db->quote(false, 'boolean'));
		} else {
			$private_where_clause = '';
		}

		$sql = sprintf($sql,
			SwatDB::equalityOperator($app->getInstanceId()),
			$app->db->quote($app->getInstanceId(), 'integer'),
			$app->db->quote(PinholePhoto::STATUS_PUBLISHED),
			$app->db->quote($date->getDate(), 'date'),
			$app->db->quote($end_date->getDate(), 'date'),
			$private_where_clause);

		$days = SwatDB::query($app->db, $sql);

		$day_count = array();
		foreach ($days as $day) {
			$day_count[$day->photo_day] = $day->photo_count;
		}

		if (isset($app->memcache))
			$app->memcache->setNs('photos', $cache_key, $day_count);

		return $day_count;
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript(SwatDate $date)
	{
		$javascript = sprintf(
			"var %s = new PinholeCalendarGadget('%s', %d, %d);",
			$this->calendar_id, $this->calendar_id,
			$date->getYear(), $date->getMonth());

		return $javascript;
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		static $count = 0;

		$this->calendar_id = 'pinhole_calendar_gadget'.$count;
		$count++;

		$this->html_head_entry_set->addEntrySet(
			XML_RPCAjax::getHtmlHeadEntrySet());

		$yui = new SwatYUI(array('event', 'animation'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addJavascript(
			'packages/pinhole/javascript/pinhole-calendar-gadget.js',
			Pinhole::PACKAGE_ID);

		$this->addStylesheet(
			'packages/pinhole/styles/pinhole-calendar-gadget.css',
			Pinhole::PACKAGE_ID);

		$this->defineDefaultTitle(Pinhole::_('Photo Calendar'));
		$this->defineDescription(Pinhole::_(
			'Displays a calendar of the last month with the photos taken.'));
	}

	// }}}
}

?>
