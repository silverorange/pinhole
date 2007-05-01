<?php

require_once 'Site/SitePageFactory.php';
require_once 'Pinhole/pages/PinholePage.php';

/**
 * Resolves and creates pages
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class PinholePageFactory extends SitePageFactory
{
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

				$page = $this->instantiatePage($class, $regs);
				break;
			}
		}

		if ($page === null) {
			// not found in page map so instantiate default page
			$params = array($app, $layout);
			$page = $this->instantiatePage('PinholePage', $params);
		}

		return $page;
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
