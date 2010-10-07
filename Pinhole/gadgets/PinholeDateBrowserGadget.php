<?php

require_once 'Site/gadgets/SiteGadget.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatDisclosure.php';
require_once 'Swat/SwatContentBlock.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'SwatI18N/SwatI18NLocale.php';

/**
 * Displays a list of years photos were taken on
 *
 * @package   Pinhole
 * @copyright 2008-2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeDateBrowserGadget extends SiteGadget
{
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$container = $this->getContainer();
		$container->display();

		$this->html_head_entry_set->addEntrySet(
			$container->getHtmlHeadEntrySet());
	}

	// }}}
	// {{{ protected function getContainer()

	protected function getContainer()
	{
		$date = new SwatDate();

		if (isset($this->app->memcache)) {
			$cache_key = sprintf('PinholeDateBrowserGadget.getContent.%s.%s',
				$date->formatLikeIntl('mm/yyyy'),
				$this->app->session->isLoggedIn() ? 'private' : 'public');

			$container = $this->app->memcache->getNs('photos', $cache_key);
			if ($container !== false)
				return $container;
		}

		$container = new SwatContainer();
		$months = $this->getMonths();

		if (count($months) == 0) {
			$content = new SwatContentBlock();
			$content->content =
				Pinhole::_('No photos have been uploaded yet.');
			$container->add($content);

			return $container;
		}

		$months_array = array();

		foreach ($months as $month) {
			$date = new SwatDate($month->photo_date);
			$key = $date->getYear().'/'.$date->getMonth();
			$months_array[$key] = $month;
		}

		$locale = SwatI18NLocale::get();

		$start_date = new SwatDate($months->getFirst()->photo_date);
		$start_year = $start_date->getYear();

		$index = (count($months) - 1);
		$end_date = new SwatDate($months->getByIndex($index)->photo_date);
		$end_year = $end_date->getYear();

		for ($year = $start_year; $year >= $end_year; $year--) {
			$year_count = 0;

			$disclosure = new SwatDisclosure();
			$disclosure->title = $year;
			$disclosure->open = false;

			ob_start();

			echo '<ul>';

			for ($i = 1; $i <= 12; $i++) {
				echo '<li class="clearfix"><div>';

				$date->setMonth($i);

				if (isset($months_array[$year.'/'.$i])) {
					$a_tag = new SwatHtmlTag('a');
					$a_tag->setContent($date->getMonthName());
					$a_tag->href = $this->app->config->pinhole->path.
						'tag?date.year='.$year.
						'/date.month='.$i;

					$a_tag->display();

					$photo_count = $months_array[$year.'/'.$i]->photo_count;
					echo '<span>'.$locale->formatNumber($photo_count).
						'</span>';

					$year_count += $photo_count;
				} else {
					$div_tag = new SwatHtmlTag('div');
					$div_tag->setContent($date->getMonthName());
					$div_tag->display();
				}

				echo '</div></li>';

				if ($i == 12 && $year_count > 0) {
					echo '<li class="clearfix"><div>';

					$a_tag = new SwatHtmlTag('a');
					$a_tag->setContent(sprintf(Pinhole::_(
						'View all photos from %s'),
						$year));

					$a_tag->href = $this->app->config->pinhole->path.
						'tag?date.year='.$year;

					$a_tag->display();

					echo '<span>'.$locale->formatNumber($year_count).
						'</span>';

					echo '</div></li>';
				}
			}

			echo '</ul>';

			$content = new SwatContentBlock();
			$content->content_type = 'text/xml';
			$content->content = ob_get_clean();
			$disclosure->add($content);
			$container->add($disclosure);
		}

		if (isset($this->app->memcache))
			$this->app->memcache->setNs('photos', $cache_key, $container);

		return $container;
	}

	// }}}
	// {{{ protected function getMonths()

	protected function getMonths()
	{
		if (isset($this->app->memcache)) {
			$cache_key = sprintf('PinholeDateBrowserGadget.getMonths.%s',
				$this->app->session->isLoggedIn() ? 'private' : 'public');

			$months = $this->app->memcache->getNs('photos', $cache_key);
			if ($months !== false)
				return $months;
		}

		$sql = "select count(PinholePhoto.id) as photo_count,
				max(convertTZ(PinholePhoto.photo_date,
				PinholePhoto.photo_time_zone)) as photo_date
			from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where ImageSet.instance %s %s and PinholePhoto.status = %s
				and PinholePhoto.photo_date is not null
				%s
			group by date_part('year', convertTZ(PinholePhoto.photo_date,
				PinholePhoto.photo_time_zone)),
				date_part('month', convertTZ(PinholePhoto.photo_date,
				PinholePhoto.photo_time_zone))
			order by photo_date desc";

		if (!$this->app->session->isLoggedIn()) {
			$private_where_clause = sprintf('and PinholePhoto.private = %s',
				$this->app->db->quote(false, 'boolean'));
		} else {
			$private_where_clause = '';
		}

		$sql = sprintf($sql,
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'),
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer'),
			$private_where_clause);

		$months = SwatDB::query($this->app->db, $sql);

		if (isset($this->app->memcache))
			$this->app->memcache->setNs('photos', $cache_key, $months);

		return $months;
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Pinhole::_('View Photos By Year'));
		$this->defineDescription(Pinhole::_(
			'Displays a list of years photos were taken.'));
	}

	// }}}
}

?>
