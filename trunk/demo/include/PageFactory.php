<?php

require_once 'Pinhole/PinholePageFactory.php';
require_once 'Pinhole/layouts/PinholeLayout.php';

/**
 * @package   PinholeDemo
 * @copyright 2007 silverorange
 */
class PageFactory extends PinholePageFactory
{
	// {{{ public static function instance()

	public static function instance()
	{
		static $instance = null;

		if ($instance === null) {
			$instance = new self();
		}

		return $instance;
	}

	// }}}
	// {{{ protected function getPageMap()

	protected function getPageMap()
	{
		return array(
			'^(photo)/([0-9]+)$'              => 'PinholeBrowserDetailsPage',
			'^(tag)$'                         => 'PinholeBrowserIndexPage',
			'^(rss)$'                         => 'PinholeRssPage',
			'^(loadphoto)/(.+)/(.+).jpg$'     => 'PinholePhotoLoaderPage',
			'^robots.txt$'                    => 'PinholeRobotsPage',
		);
	}

	// }}}
	// {{{ protected function resolveLayout()

	protected function resolveLayout(SiteWebApplication $app, $source)
	{
		$layout = new PinholeLayout($app, 'Pinhole/layouts/xhtml/default.php');
		return $layout;
	}

	// }}}
}

?>
