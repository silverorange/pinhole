<?php

require_once 'Site/SiteGadget.php';
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
				Pinhole::_('%s photos have been uploaded since %s'),
				$locale->formatNumber($stats->photo_count),
				$first_date->format(SwatDate::DF_DATE)));

			$li_tag->display();

			$li_tag->setContent(sprintf(
				Pinhole::_('Last photo uploaded on %s'),
				$last_date->format(SwatDate::DF_DATE)));

			$li_tag->display();

			$tag_stats = $this->getTagStats();

			if ($tag_stats->tag_count > 0) {
				$li_tag = new SwatHtmlTag('li');
				$li_tag->setContent(sprintf(
					Pinhole::_('%s tags have been added'),
					$locale->formatNumber($tag_stats->tag_count)));

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
		$sql = "select count(PinholePhoto.id) as photo_count,
				max(convertTZ(PinholePhoto.photo_date,
				PinholePhoto.photo_time_zone)) as last_date,
				min(convertTZ(PinholePhoto.photo_date,
				PinholePhoto.photo_time_zone)) as first_date
			from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where ImageSet.instance %s %s and PinholePhoto.status = %s";

		$sql = sprintf($sql,
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'),
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer'));

		return SwatDB::queryRow($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function getTagStats()

	protected function getTagStats()
	{
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
			group by PinholeTag.id
			order by tag_count desc";

		$sql = sprintf($sql,
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'),
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer'));

		$popular_tag_info = SwatDB::queryRow($this->app->db, $sql);

		$sql = sprintf('select title, name from PinholeTag
			where PinholeTag.id = %s',
			$this->app->db->quote($popular_tag_info->id, 'integer'));

		$popular_tag = SwatDB::queryRow($this->app->db, $sql);

		$stats = new StdClass();
		$stats->tag_count = $tag_count;
		$stats->popular_tag_title = $popular_tag->title;
		$stats->popular_tag_name = $popular_tag->name;
		$stats->popular_tag_count = $popular_tag_info->tag_count;
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
