<?php

require_once 'Site/SiteGadget.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatDisclosure.php';
require_once 'Swat/SwatContentBlock.php';
require_once 'SwatI18N/SwatI18NLocale.php';
require_once 'Pinhole/dataobjects/PinholePhotoMetaDataBinding.php';

/**
 * Displays some statistics about the current gallery
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeMetaDataGadget extends SiteGadget
{
	// {{{ private properties

	private $container;

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$sql = sprintf("select PinholeMetaData.* from PinholeMetaData
			where PinholeMetaData.instance %s %s and visible = %s
				and machine_tag = %s
			order by title",
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'),
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote(true, 'boolean'));

		$meta_data = SwatDB::query($this->app->db, $sql);

		$sql = "select count(photo) as photo_count, meta_data, value
			from PinholePhotoMetaDataBinding
			inner join PinholeMetaData on PinholeMetaData.id =
				PinholePhotoMetaDataBinding.meta_data
			inner join PinholePhoto on PinholePhotoMetaDataBinding.photo =
				PinholePhoto.id
			where PinholeMetaData.instance %s %s and PinholePhoto.status = %s
				and PinholeMetaData.visible = %s
				and PinholeMetaData.machine_tag = %s
			group by meta_data, value";

		$sql = sprintf($sql,
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'),
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED),
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote(true, 'boolean'));

		$rows = SwatDB::query($this->app->db, $sql);
		$locale = SwatI18NLocale::get();

		$this->container = new SwatContainer();

		$sorted_rows = array();
		foreach ($rows as $row)
			$sorted_rows[] = $row;

		usort($sorted_rows, array(get_class($this), 'sortMetaData'));

		foreach ($meta_data as $meta) {
			$disclosure = new SwatDisclosure();
			$disclosure->title = $meta->title;
			$disclosure->open = false;

			ob_start();

			echo '<ul>';
			foreach ($sorted_rows as $row) {
				if ($row->meta_data == $meta->id) {
					echo '<li class="clearfix"><div>';

					$a_tag = new SwatHtmlTag('a');
					$a_tag->href = sprintf('%stag?meta.%s=%s',
						$this->app->config->pinhole->path,
						$meta->shortname,
						PinholePhotoMetaDataBinding::escapeValue($row->value));

					$a_tag->setContent($row->value);
					$a_tag->display();

					echo ' <span>'.$locale->formatNumber($row->photo_count).
						'</span>';

					echo '</div></li>';
				}
			}
			echo '</ul>';

			$content = new SwatContentBlock();
			$content->content_type = 'text/xml';
			$content->content = ob_get_clean();
			$disclosure->add($content);
			$this->container->add($disclosure);
		}

		$this->container->display();
		$this->html_head_entry_set->addEntrySet($this->container->getHtmlHeadEntrySet());
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Pinhole::_('Metadata Browser (Beta)'));
		$this->defineDescription(Pinhole::_(
			'Display information about photo meta data and '.
			'allow browsing by meta data'));
	}

	// }}}
	// {{{ private static function sortMetaData()

	private static function sortMetaData($a, $b)
	{
		if (floatval($a->value) > 0 && floatval($b->value) > 0) {
			$al = floatval($a->value);
			$bl = floatval($b->value);
		} else {
			$al = strtolower($a->value);
			$bl = strtolower($b->value);
		}

		if ($al == $bl) {
			return 0;
		}

		return ($al > $bl) ? +1 : -1;
	}

	// }}}
}

?>
