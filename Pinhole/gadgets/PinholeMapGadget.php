<?php

/**
 * Displays a map of geo-tagged photos
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeMapGadget extends SiteGadget
{
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$cache_module = (isset($this->app->memcache)) ?
			$this->app->memcache : null;

		$tag_list = new PinholeTagList($this->app, null,
			$this->app->session->isLoggedIn());

		if ($tag_list->getGeoTaggedPhotoCount() == 0) {
			echo Pinhole::_('There are no tags with geo-tagged data.');
		} else {
			$locale = SwatI18NLocale::get();

			echo sprintf(Pinhole::_('%s photos can be %sviewed on a map%s.'),
				$locale->formatNumber($tag_list->getGeoTaggedPhotoCount()),
				'<a href="'.$this->app->config->pinhole->path.'map'.'">',
				'</a>');

			$map = new PinholeStaticMapView();
			$map->setTagList($tag_list);
			$map->api_key = $this->app->config->pinhole->google_maps_api_key;
			$map->width = 250;
			$map->height = 150;
			$map->base = $this->app->config->pinhole->path.'map';
			$map->display();
		}

	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Pinhole::_('Map'));
		$this->defineDescription(Pinhole::_(
			'Displays a map graphic with a link to the full map page.'));
	}

	// }}}
}

?>
