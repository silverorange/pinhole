<?php

/**
 * Displays some statistics about the current gallery
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeMetaDataGadget extends SiteGadget
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
		if (isset($this->app->memcache)) {
			$cache_key = sprintf('PinholeMetaDataGadget.getContainer.%s',
				$this->app->session->isLoggedIn() ? 'private' : 'public');

			$container = $this->app->memcache->getNs('photos', $cache_key);
			if ($container !== false)
				return $container;
		}

		$sections = $this->getMetaDataSections();
		$values = $this->getMetaDataValues();

		$locale = SwatI18NLocale::get();
		$container = new SwatContainer();

		foreach ($sections as $section) {
			$disclosure = new SwatDisclosure();
			$disclosure->title = $section->title;
			$disclosure->open = false;

			ob_start();
			echo '<ul>';
			foreach ($values as $value) {
				if ($value->meta_data == $section->id) {
					echo '<li class="clearfix"><div>';

					$a_tag = new SwatHtmlTag('a');
					$a_tag->href = sprintf('%stag?meta.%s=%s',
						$this->app->config->pinhole->path,
						$section->shortname,
						PinholePhotoMetaDataBinding::escapeValue(
							$value->value));

					$a_tag->setContent(
						PinholePhotoMetaDataBinding::getFormattedValue(
							$section->shortname, $value->value));

					$a_tag->display();

					echo ' <span>'.$locale->formatNumber(
						$value->photo_count).'</span>';

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
	// {{{ protected function getMetaDataSections()

	protected function getMetaDataSections()
	{
		if (isset($this->app->memcache)) {
			$cache_key = sprintf('PinholeMetaDataGadget.sections.%s',
				$this->app->session->isLoggedIn() ? 'private' : 'public');

			$sections = $this->app->memcache->get($cache_key);
			if ($sections !== false)
				return $sections;
		}

		$sql = sprintf("select PinholeMetaData.* from PinholeMetaData
			where PinholeMetaData.instance %s %s and visible = %s
				and machine_tag = %s
			order by displayorder, title",
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'),
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote(true, 'boolean'));

		$sections = SwatDB::query($this->app->db, $sql);

		if (isset($this->app->memcache))
			$this->app->memcache->set($cache_key, $sections);

		return $sections;
	}

	// }}}
	// {{{ protected function getMetaDataValues()

	protected function getMetaDataValues()
	{
		if (isset($this->app->memcache)) {
			$cache_key = sprintf('PinholeMetaDataGadget.getMetaDataValues.%s',
				$this->app->session->isLoggedIn() ? 'private' : 'public');

			$values = $this->app->memcache->get($cache_key);
			if ($values !== false)
				return $values;
		}

		$sql = "select count(photo) as photo_count, meta_data, value,
				PinholeMetaData.shortname
			from PinholePhotoMetaDataBinding
			inner join PinholeMetaData on PinholeMetaData.id =
				PinholePhotoMetaDataBinding.meta_data
			inner join PinholePhoto on PinholePhotoMetaDataBinding.photo =
				PinholePhoto.id
			where PinholeMetaData.instance %s %s and PinholePhoto.status = %s
				and PinholeMetaData.visible = %s
				and PinholeMetaData.machine_tag = %s
				%s
			group by meta_data, value, shortname";

		if (!$this->app->session->isLoggedIn()) {
			$private_where_clause = sprintf('and PinholePhoto.private = %s',
				$this->app->db->quote(false, 'boolean'));
		} else {
			$private_where_clause = '';
		}

		$sql = sprintf($sql,
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'),
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED),
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote(true, 'boolean'),
			$private_where_clause);

		$rows = SwatDB::query($this->app->db, $sql);

		$sorted_values = array();
		foreach ($rows as $row) {
			$sorted_values[] = $row;
		}

		usort($sorted_values, array(get_class($this), 'sortMetaData'));

		if (isset($this->app->memcache))
			$this->app->memcache->set($cache_key, $sorted_values);

		return $sorted_values;
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
		if (floatval($a->value) != 0 && floatval($b->value) != 0) {
			$al = floatval($a->value);
			$bl = floatval($b->value);
		} else {
			$al = mb_strtolower($a->value);
			$bl = mb_strtolower($b->value);
		}

		if ($al == $bl) {
			return 0;
		}

		return ($al > $bl) ? +1 : -1;
	}

	// }}}
}

?>
