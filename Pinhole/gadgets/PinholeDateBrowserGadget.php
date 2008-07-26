<?php

require_once 'Site/SiteGadget.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'SwatI18N/SwatI18NLocale.php';

/**
 * Displays a list of years photos were taken on
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeDateBrowserGadget extends SiteGadget
{
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$sql = "select count(PinholePhoto.id) as photo_count,
				max(convertTZ(PinholePhoto.photo_date,
				PinholePhoto.photo_time_zone)) as photo_date
			from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where ImageSet.instance %s %s and PinholePhoto.status = %s
				and PinholePhoto.photo_date is not null
			group by date_part('year', convertTZ(PinholePhoto.photo_date,
				PinholePhoto.photo_time_zone))
			order by photo_date desc";

		$sql = sprintf($sql,
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'),
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer'));

		$years = SwatDB::query($this->app->db, $sql);
		$locale = SwatI18NLocale::get();

		echo '<ul>';

		foreach ($years as $year) {
			$date = new SwatDate($year->photo_date);
			$date->getYear();

			$li_tag = new SwatHtmlTag('li');
			$li_tag->open();

			$a_tag = new SwatHtmlTag('a');
			$a_tag->href = $this->app->config->pinhole->path.'tag?date.year='.
				$date->getYear();

			$a_tag->setContent($date->getYear());

			$a_tag->display();

			echo ' '.$locale->formatNumber($year->photo_count).' '.
				Pinhole::ngettext('Photo', 'Photos', $year->photo_count);

			$li_tag->close();
		}

		echo '</ul>';
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Pinhole::_('View Photos By Date'));
		$this->defineDescription(Pinhole::_(
			'Displays a list of years photos were taken.'));
	}

	// }}}
}

?>
