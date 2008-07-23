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
	public function __construct(SiteApplication $app)
	{
		parent::__construct($app);

		// set location to load Pinhole page classes from
		$this->page_class_map['Pinhole'] = 'Pinhole/pages';
	}

	// }}}
	// {{{ public function get()

	/**
	 * Resolves a page object from a source string
	 *
	 * @param string $source the source string for which to get the page.
	 * @param SiteLayout $layout optional, the layout to use.
	 *
	 * @return SitePage the page for the given source string.
	 */
	public function get($source, SiteLayout $layout = null)
	{
		$layout = ($layout === null) ? $this->getLayout($source) : $layout;

		if ($source == '')
			$source = 'tag';

		$page_info = $this->getPageInfo($source);

		if ($page_info === null) {
			throw new SiteNotFoundException();
		}

		// create page object
		$page = $this->getPage($page_info['page'], $layout,
			$page_info['arguments']);

		// decorate page
		$decorators = array_reverse($page_info['decorators']);
		foreach ($decorators as $decorator) {
			$page = $this->decorate($page, $decorator);
		}

		return $page;
	}

	// }}}
	// {{{ protected function getPageInfo()

	/**
	 * Gets page info for the passed source string
	 *
	 * @param string $source the source string for which to get the page info.
	 *
	 * @return array an array of page info. The array has the index values
	 *               'page', 'path', 'decorators' and 'arguments'. If no
	 *               suitable page is found, null is returned.
	 */
	protected function getPageInfo($source)
	{
		$info = null;

		foreach ($this->getPageMap() as $pattern => $class) {
			$regs = array();
			$pattern = str_replace('@', '\@', $pattern); // escape delimiters
			$regexp = '@'.$pattern.'@u';
			if (preg_match($regexp, $source, $regs) === 1) {

				$info = array(
					'page'       => $this->default_page_class,
					'path'       => $source,
					'decorators' => array(),
					'arguments'  => array(),
				);

				array_shift($regs); // discard full match string

				// get path as first subpattern
				$info['path'] = array_shift($regs);

				// get additional arguments as remaining subpatterns
				foreach ($regs as $reg) {
					// set empty regs parsed from page map expressions to null
					$reg = ($reg == '') ? null : $reg;
					$info['arguments'][] = $reg;
				}

				// get page class and/or decorators
				if (is_array($class)) {
					$page = array_pop($class);
					if ($this->isPage($page)) {
						$info['page']       = $page;
						$info['decorators'] = $class;
					} else {
						$class[]            = $page;
						$info['decorators'] = $class;
					}
				} else {
					if ($this->isPage($class)) {
						$info['page'] = $class;
					} else {
						$info['decorators'][] = $class;
					}
				}

				break;
			}
		}

		return $info;
	}

	// }}}
	// {{{ protected function getPageMap()

	protected function getPageMap()
	{
		return array(
			'^(photo)/(\d+)/([\w\d*]+)$'  => 'PinholeBrowserDetailsPage',
			'^(photo)/(\d+)$'             => 'PinholeBrowserDetailsPage',
			'^(tag)$'                     => 'PinholeBrowserIndexPage',
			'^(tags)$'                    => 'PinholeBrowserTagPage',
			'^(tags)/(date)$'             => 'PinholeBrowserTagPage',
			'^(tags)/(alphabetical)$'     => 'PinholeBrowserTagPage',
			'^(tags)/(popular)$'          => 'PinholeBrowserTagPage',
			'^(tags)/(cloud)$'            => 'PinholeBrowserTagPage',
			'^(feed)$'                    => 'PinholeAtomPage',
			'^(feed)/([\w\d*]+)$'         => 'PinholeAtomPage',
			'^(loadphoto)/(.+)/(.+).jpg$' => 'PinholePhotoLoaderPage',
			'^(login)$'                   => 'PinholeLoginPage',
			'^robots.txt$'                => 'PinholeRobotsPage',
		);
	}

	// }}}
}

?>
