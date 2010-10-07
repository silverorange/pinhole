<?php

require_once 'Site/gadgets/SiteGadget.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'SwatI18N/SwatI18NLocale.php';

/**
 * Displays some statistics about the current gallery
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeStatisticsGadget extends SiteGadget
{
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$stats = $this->getPhotoStats();

		if ($stats->last_date === null) {
			echo Pinhole::_('No photos have been added.');
		} else {
			$locale = SwatI18NLocale::get();

			$first_date = new SwatDate($stats->first_date);
			$last_date = new SwatDate($stats->last_date);

			echo '<ul>';

			$li_tag = new SwatHtmlTag('li');
			$li_tag->setContent(sprintf(
				Pinhole::_('%s photos have been uploaded since '.
					'<a href="%stag?date.date=%s">%s</a>'),
				$locale->formatNumber($stats->photo_count),
				$this->app->config->pinhole->path,
				$first_date->formatLikeIntl('yyyy-MM-dd'),
				$first_date->formatLikeIntl(SwatDate::DF_DATE)), 'text/xml');

			$li_tag->display();

			$days = $last_date->diff($first_date)->days;
			$avg = round(((float)$stats->photo_count / (float)$days), 2);
			$li_tag = new SwatHtmlTag('li');
			$li_tag->setContent(sprintf(
				Pinhole::_('Approximately %s photos have been uploaded '.
					'per day'),
				$locale->formatNumber($avg)));

			$li_tag->display();

			$li_tag->setContent(sprintf(
				Pinhole::_('Last photo uploaded on '.
					'<a href="%stag?date=%s">%s</a>'),
				$this->app->config->pinhole->path,
				$last_date->formatLikeIntl('yyyy-MM-dd'),
				$last_date->formatLikeIntl(SwatDate::DF_DATE)), 'text/xml');

			$li_tag->display();

			$tag_stats = $this->getTagStats();

			if ($tag_stats->tag_count > 0) {
				$li_tag = new SwatHtmlTag('li');
				$li_tag->setContent(sprintf(
					Pinhole::_('<a href="%s/tags/alphabetical">%s tags</a> '.
						'have been added'),
					$this->app->config->pinhole->path,
					$locale->formatNumber($tag_stats->tag_count)), 'text/xml');

				$li_tag->display();

				$a_tag = new SwatHtmlTag('a');
				$a_tag->setContent($tag_stats->popular_tag_title);
				$a_tag->href = $this->app->config->pinhole->path.'tag?'.
					SwatString::minimizeEntities($tag_stats->popular_tag_name);

				$li_tag = new SwatHtmlTag('li');
				$li_tag->setContent(sprintf(
					Pinhole::_('The most popular tag “%s” has %s photos'),
					(string) $a_tag,
					$locale->formatNumber($tag_stats->popular_tag_count)),
					'text/xml');

				$li_tag->display();
			}

			echo '</ul>';
		}
	}

	// }}}
	// {{{ protected function getPhotoStats()

	protected function getPhotoStats()
	{
		if (isset($this->app->memcache)) {
			$cache_key = 'PinholeStatisticsGadget.photo_stats';
			$value = $this->app->memcache->getNs('photos', $cache_key);
			if ($value !== false)
				return $value;
		}

		$sql = "select count(PinholePhoto.id) as photo_count,
				max(convertTZ(PinholePhoto.photo_date,
				PinholePhoto.photo_time_zone)) as last_date,
				min(convertTZ(PinholePhoto.photo_date,
				PinholePhoto.photo_time_zone)) as first_date
			from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where ImageSet.instance %s %s and PinholePhoto.status = %s
			%s";

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

		$stats = SwatDB::queryRow($this->app->db, $sql);

		if (isset($this->app->memcache))
			$this->app->memcache->setNs('photos', $cache_key, $stats);
		return $stats;
	}

	// }}}
	// {{{ protected function getTagStats()

	protected function getTagStats()
	{
		if (isset($this->app->memcache)) {
			$cache_key = 'PinholeStatisticsGadget.tag_stats';
			$value = $this->app->memcache->getNs('photos', $cache_key);
			if ($value !== false)
				return $value;
		}

		$sql = "select count(distinct PinholePhotoTagBinding.tag) as tag_count
			from PinholePhotoTagBinding
			inner join PinholePhoto on
				PinholePhoto.id = PinholePhotoTagBinding.photo
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where ImageSet.instance %s %s and PinholePhoto.status = %s";

		$sql = sprintf($sql,
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'),
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer'));

		$tag_count = SwatDB::queryOne($this->app->db, $sql);

		$sql = "select count(PinholePhotoTagBinding.photo) as tag_count,
				PinholeTag.id
			from PinholePhotoTagBinding
			inner join PinholeTag on PinholePhotoTagBinding.tag = PinholeTag.id
			inner join PinholePhoto on
				PinholePhoto.id = PinholePhotoTagBinding.photo
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where ImageSet.instance %s %s and PinholePhoto.status = %s
			%s
			group by PinholeTag.id
			order by tag_count desc";

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

		$popular_tag_info = SwatDB::queryRow($this->app->db, $sql);

		if ($popular_tag_info === null) {
			$stats = new StdClass();
			$stats->tag_count = 0;
			return $stats;
		} else {
			$sql = sprintf('select title, name from PinholeTag
				where PinholeTag.id = %s',
				$this->app->db->quote($popular_tag_info->id, 'integer'));

			$popular_tag = SwatDB::queryRow($this->app->db, $sql);

			$stats = new StdClass();
			$stats->tag_count = $tag_count;
			$stats->popular_tag_title = $popular_tag->title;
			$stats->popular_tag_name = $popular_tag->name;
			$stats->popular_tag_count = $popular_tag_info->tag_count;

			if (isset($this->app->memcache))
				$this->app->memcache->setNs('photos', $cache_key, $stats);
		}

		return $stats;
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Pinhole::_('Statistics'));
		$this->defineDescription(Pinhole::_(
			'Displays some statistics about the current site.'));
	}

	// }}}
}

?>
