<?php

require_once 'Swat/SwatDetailsStore.php';
require_once 'Admin/pages/AdminPage.php';
//require_once 'Pinhole/dataobjects/PinholeFile.php';

/**
 * Shows editable configuration values for a Pinhole site
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeConfigIndex extends AdminPage
{
	// {{{ protected properties

	protected $ui_xml = 'Pinhole/admin/components/Config/index.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->buildMessages();
		$this->buildSiteSettingsView();
		$this->buildAdSettingsView();
	}

	// }}}
	// {{{ protected function buildSiteSettingsView()

	protected function buildSiteSettingsView()
	{
		$setting_keys = array(
			'site' => array(
				'title',
				'meta_description',
			),
			'pinhole' => array(
				'header_image',
				'search_engine_indexable',
			),
			'date' => array(
				'time_zone',
			),
			'analytics' => array(
				'google_account',
			),
		);

		$ds = new SwatDetailsStore();

		foreach ($setting_keys as $section => $keys) {
			foreach ($keys as $name) {
				$field_name = $section.'_'.$name;

				$details_method = 'buildDetails'.str_replace(' ', '',
					ucwords(str_replace('_', ' ', $field_name)));

				if (method_exists($this, $details_method)) {
					$this->$details_method($ds);
				} else {
					$ds->$field_name = $this->app->config->$section->$name;
				}
			}
		}

		$ds->pinhole_search_engine_indexable =
			($this->app->config->pinhole->search_engine_indexable == '1');

		$view = $this->ui->getWidget('config_settings_view');
		$view->data = $ds;
	}

	// }}}
	// {{{ protected function buildAdSettingsView()

	protected function buildAdSettingsView()
	{
		$setting_keys = array(
			'pinhole' => array(
				'ad_bottom',
				'ad_top',
				'ad_referers_only',
			),
		);

		$ds = new SwatDetailsStore();

		foreach ($setting_keys as $section => $keys) {
			foreach ($keys as $name) {
				$field_name = $section.'_'.$name;

				$details_method = 'buildDetails'.str_replace(' ', '',
					ucwords(str_replace('_', ' ', $field_name)));

				if (method_exists($this, $details_method)) {
					$this->$details_method($ds);
				} else {
					$ds->$field_name = $this->app->config->$section->$name;
				}
			}
		}

		$view = $this->ui->getWidget('ad_settings_view');
		$view->data = $ds;
	}

	// }}}
	// {{{ protected function buildDetailsPinholeHeaderImage()

	protected function buildDetailsPinholeHeaderImage(SwatDetailsStore $ds)
	{
		$ds->pinhole_header_image = '';
		$ds->has_pinhole_header_image = false;

		$header_image = $this->app->config->pinhole->header_image;
		if ($header_image != '') {
			/* TODO
			$class = SwatDBClassMap::get('PinholeFile');
			$file = new $class();
			$file->setDatabase($this->app->db);
			if ($file->load(intval($header_image))) {
				$path = $file->getRelativeUri('../');
				$ds->pinhole_header_image = $path;
				$ds->has_pinhole_header_image = true;
			}
			*/
		}
	}

	// }}}
	// {{{ protected function buildDetailsPinholeAdTop()

	protected function buildDetailsPinholeAdTop(SwatDetailsStore $ds)
	{
		$ds->pinhole_ad_top = ($this->app->config->pinhole->ad_top != '');
	}

	// }}}
	// {{{ protected function buildDetailsPinholeAdBottom()

	protected function buildDetailsPinholeAdBottom(SwatDetailsStore $ds)
	{
		$ds->pinhole_ad_bottom = ($this->app->config->pinhole->ad_bottom != '');
	}

	// }}}
}

?>
