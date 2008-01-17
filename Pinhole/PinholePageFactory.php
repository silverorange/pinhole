<?php

require_once 'Site/SitePageFactory.php';
require_once 'Pinhole/pages/PinholePage.php';
require_once 'Pinhole/layouts/PinholeLayout.php';

/**
 * Resolves and creates pages
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePageFactory extends SitePageFactory
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
	// {{{ public function resolvePage()

	/**
	 * Resolves a page object from a source string
	 *
	 * @param SiteWebApplication $app the web application for which the page is
	 *                               being resolved.
	 * @param string $source the source string for which to get the page.
	 *
	 * @return SitePage the page for the given source string.
	 */
	public function resolvePage(SiteWebApplication $app, $source)
	{
		$layout = $this->resolveLayout($app, $source);
		$article_path = $source;

		$page = null;

		foreach ($this->getPageMap() as $pattern => $class) {
			$regs = array();
			$pattern = str_replace('@', '\@', $pattern); // escape delimiters
			$regexp = '@'.$pattern.'@u';
			if (preg_match($regexp, $source, $regs) === 1) {
				array_shift($regs); // discard full match string
				$path = array_shift($regs);
				array_unshift($regs, $layout);
				array_unshift($regs, $app);

				$page = $this->instantiatePage($app, $class, $regs);
				break;
			}
		}

		if ($page === null)
			throw new SiteNotFoundException();

		return $page;
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
	// {{{ protected function __construct()

	/**
	 * Creates a PinholePageFactory
	 */
	protected function __construct()
	{
		parent::__construct();

		// set location to load Pinhole page classes from
		$this->class_map['Pinhole'] = 'Pinhole/pages';
	}

	// }}}
}

?>
