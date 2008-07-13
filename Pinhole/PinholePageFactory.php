<?php

require_once 'Site/SitePageFactory.php';

/**
 * Resolves and creates pages
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePageFactory extends SitePageFactory
{
	// {{{ public function __construct()

	/**
	 * Creates a PinholePageFactory
	 */
	public function __construct()
	{
		// set location to load Pinhole page classes from
		$this->class_map['Pinhole'] = 'Pinhole/pages';
	}

	// }}}
	// {{{ public function resolvePage()

	/**
	 * Resolves a page object from a source string
	 *
	 * @param SiteWebApplication $app the web application for which the page is
	 *                               being resolved.
	 * @param string $source the source string for which to get the page.
	 * @param SiteLayout $layout optional, the layout to use.
	 *
	 * @return SitePage the page for the given source string.
	 */
	public function resolvePage(SiteWebApplication $app, $source,
		$layout = null)
	{
		if ($layout === null)
			$layout = $this->resolveLayout($app, $source);

		if ($source == '')
			$source = 'tag';

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
			'^(photo)/([0-9]+)/([\w\d*]+)$'   => 'PinholeBrowserDetailsPage',
			'^(photo)/([0-9]+)$'              => 'PinholeBrowserDetailsPage',
			'^(tag)$'                         => 'PinholeBrowserIndexPage',
			'^(tags)$'                        => 'PinholeBrowserTagPage',
			'^(tags)/(alphabetical)$'         => 'PinholeBrowserTagPage',
			'^(tags)/(popular)$'              => 'PinholeBrowserTagPage',
			'^(tags)/(cloud)$'                => 'PinholeBrowserTagPage',
			'^(feed)$'                        => 'PinholeAtomPage',
			'^(feed)/([\w\d*]+)$'             => 'PinholeAtomPage',
			'^(loadphoto)/(.+)/(.+).jpg$'     => 'PinholePhotoLoaderPage',
			'^robots.txt$'                    => 'PinholeRobotsPage',
		);
	}

	// }}}
}

?>
