<?php

require_once 'Swat/SwatYUI.php';
require_once 'Pinhole/pages/PinholeBrowserPage.php';
require_once 'Pinhole/tags/PinholePageTag.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeBrowserMapPage extends PinholeBrowserPage
{
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null,
		array $arguments = array())
	{
		$app->memcache->flush();

		parent::__construct($app, $layout, $arguments);
		$this->ui_xml = 'Pinhole/pages/browser-map.xml';
	}

	// }}}
	// {{{ protected function initTagList()

	protected function initTagList()
	{
		$this->tag_list->setShowOnlyGeoTaggedPhotos(true);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->buildLayout();
	}

	// }}}
	// {{{ protected function buildTagListView()

	protected function buildTagListView()
	{
		parent::buildTagListView();

		if ($this->ui->hasWidget('tag_list_view'))
			$this->ui->getWidget('tag_list_view')->base = 'map';
	}

	// }}}
	// {{{ protected function buildLayout()

	protected function buildLayout()
	{
		// Set YUI Grid CSS class for one full-width column on map page.
		$this->layout->data->yui_grid_class = 'yui-t7';
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		echo '<div id="map"></div>';

		echo Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		$script = sprintf("var map_obj = new PinholeMap(%s, %s);\n",
			SwatString::quoteJavaScriptString('map'),
			SwatString::quoteJavaScriptString($this->tag_list->__toString()));

		$photos = $this->tag_list->getPhotos('small',
			array('gps_longitude', 'gps_latitude'));

		$markers = array();

		foreach ($photos as $photo) {
			// group photos that are very close to each other
			$rounded_lat  = round($photo->gps_latitude, 4);
			$rounded_long = round($photo->gps_longitude, 4);
			$key = $rounded_lat.'_'.$rounded_long;

			if (array_key_exists($key, $markers)) {
				$markers[$key]->photos[] = $photo->id;
			} else {
				$marker = new StdClass();
				$marker->photos    = array($photo->id);
				$marker->latitude  = $rounded_lat;
				$marker->longitude = $rounded_long;
				$markers[$key] = $marker;
			}
		}

		foreach ($markers as $marker) {
			$script.= sprintf(
				"map_obj.addMarker(new PinholeMapMarker(%s, %s, [%s]));\n",
				(float) $marker->latitude,
				(float) $marker->longitude,
				implode(', ', $marker->photos));
		}

		return $script;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$yui = new SwatYUI(array('dom', 'event'));
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatJavascriptHtmlHeadEntry(
			'packages/pinhole/javascript/pinhole-map.js'), Pinhole::PACKAGE_ID);

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/swat/styles/swat-tile-view.css'), Swat::PACKAGE_ID);

		$this->layout->addHtmlHeadEntry(new SwatJavascriptHtmlHeadEntry(
			'packages/swat/javascript/swat-view.js'), Swat::PACKAGE_ID);

		$this->layout->addHtmlHeadEntry(new SwatJavascriptHtmlHeadEntry(
			'packages/swat/javascript/swat-tile-view.js'), Swat::PACKAGE_ID);

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/site/styles/site-image-cell-renderer.css'), Site::PACKAGE_ID);

		$this->layout->addHtmlHeadEntrySet(
			XML_RPCAjax::getHtmlHeadEntrySet());


		$this->layout->startCapture('html_head_entries');
		echo "\n\n<!-- head entries for Google Maps -->\n";

		printf('<script src="http://maps.google.com/maps?file=api&amp;'.
			'v=2&amp;sensor=false&amp;key=%s"></script>',
			$this->app->config->pinhole->google_maps_api_key);

		echo "\n";

		echo '<script src="packages/pinhole/javascript/'.
			'pinhole-marker-cluster.js"></script>';

		echo "\n\n";

		$this->layout->endCapture();

		/*
		$this->layout->addHtmlHeadEntry(new SwatJavascriptHtmlHeadEntry(
			'packages/pinhole/javascript/pinhole-marker-cluster.js'),
			Pinhole::PACKAGE_ID);
		*/
	}

	// }}}
}

?>
