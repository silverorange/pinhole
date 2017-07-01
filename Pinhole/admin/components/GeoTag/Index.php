<?php

/**
 * Index page for geo-tagging
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeGeoTagIndex extends AdminPage
{
	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$config = $this->app->config;
		$this->layout->startCapture('content');

		if ($config->pinhole->google_maps_api_key === null) {
			echo Pinhole::_('You must specify a google maps api key '.
				'to use geo-tagging features.');
		} elseif ($config->pinhole->google_maps_admin_api_key === null) {
			echo Pinhole::_('You must specify an admin google maps api key '.
				'to use geo-tagging features.');
		} else {
			printf('<iframe src="%s/Search" frameborder="0" '.
				'id="search_iframe"></iframe>',
				$this->getComponentName());

			echo '<div id="map_frame">';
			echo '<form method="post" action="#">';
			printf('<input type="submit" id="set_gps" value="%s" />',
				Pinhole::_('Geo-Tag Photo(s)'));

			printf(' <input type="checkbox" id="auto_next" /> '.
				'<label for="auto_next">%s</label>',
				Pinhole::_('Automatically proceed to next photo'));

			echo '</form>';
			echo '<div id="map"></div>';
			echo '</div>';

			echo Swat::displayInlineJavaScript($this->getInlineJavaScript());
		}

		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		$config = $this->app->config->pinhole;

		$start_latitude = ($config->map_last_latitude === null) ?
			"''" : $config->map_last_latitude;

		$start_longitude = ($config->map_last_longitude === null) ?
			"''" : $config->map_last_longitude;

		$start_zoom_level = ($config->map_last_zoom_level === null) ?
			"''" : $config->map_last_zoom_level;

		$script = sprintf("var map_obj = new PinholeGeoTagMap(".
			"%s, %s, %s, %s);\n",
			SwatString::quoteJavaScriptString('map'),
			$start_latitude, $start_longitude, $start_zoom_level);

		return $script;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntrySet(XML_RPCAjax::getHtmlHeadEntrySet());

		$yui = new SwatYUI(array('dom', 'event'));
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			'packages/pinhole/admin/javascript/pinhole-geo-tag-map.js',
			Pinhole::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/admin/styles/pinhole-geo-tag-index.css',
			Pinhole::PACKAGE_ID));

		$this->layout->startCapture('html_head_entries');
		echo "\n\n<!-- head entries for Google Maps -->\n";

		printf('<script src="http://maps.google.com/maps?file=api&amp;'.
			'v=2&amp;sensor=false&amp;key=%s"></script>',
			$this->app->config->pinhole->google_maps_admin_api_key);

		echo "\n\n";

		$this->layout->endCapture();
	}

	// }}}
}

?>
