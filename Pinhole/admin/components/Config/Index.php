<?php

require_once 'Swat/SwatDetailsStore.php';
require_once 'Site/admin/SiteThemeDisplay.php';
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
		$this->buildTheme();
		$this->buildSiteSettingsView();
		$this->buildAdSettingsView();
	}

	// }}}
	// {{{ protected function buildTheme()

	protected function buildTheme()
	{
		$current_theme = $this->app->config->site->theme;

		$themes = $this->app->theme->getAvailable();
		foreach ($themes as $theme) {
			if ($theme->getShortname() == $current_theme) {
				$theme_display = $this->ui->getWidget('theme');
				$theme_display->selected = true;
				$theme_display->setTheme($theme);
				break;
			}
		}
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
				'photos_per_page',
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
