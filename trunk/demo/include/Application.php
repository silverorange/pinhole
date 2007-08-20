<?php

require_once 'Pinhole/layouts/PinholeLayout.php';
require_once 'Pinhole/PinholeMultipleInstanceModule.php';
require_once 'Site/SiteWebApplication.php';
require_once 'Site/SiteConfigModule.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'SwatDB/SwatDBClassMap.php';

SwatDBClassMap::addPath(dirname(__FILE__).'/dataobjects');

/**
 * Pinhole-based demo gallery
 *
 * @package   PinholeDemo
 * @copyright 2007 silverorange
 */
class Application extends SiteWebApplication
{
	// {{{ protected function loadPage()

	protected function loadPage()
	{
		if (isset($this->config->locale))
			setlocale($this->config->locale);
		else
			setlocale(LC_ALL, 'en_CA.UTF-8');

		parent::loadPage();
	}

	// }}}
	// {{{ protected function resolvePage()

	/**
	 * Resolves page from a source string
	 * @return SwatPage A subclass of SwatPage is returned.
	 */
	protected function resolvePage($source)
	{
		$path = $this->explodeSource($source);

		if (count($path) == 0)
			// TODO: relocate since there is no separate front page right now
			$this->relocate('tag');
		else
			$tag = $path[0];

		switch ($tag) {
			case 'httperror':
				require_once 'Site/pages/SiteHttpErrorPage.php';
				$layout = new PinholeLayout($this,
					'Pinhole/layouts/xhtml/default.php');

				$page = new SiteHttpErrorPage($this, $layout);
				break;

			case 'exception':
				require_once 'Pinhole/pages/PinholeExceptionPage.php';
				$layout = new PinholeLayout($this,
					'Pinhole/layouts/xhtml/default.php');

				$page = new PinholeExceptionPage($this, $layout);
				break;
				
			default:
				require_once '../include/PageFactory.php';
				$factory = PageFactory::instance();
				$page = $factory->resolvePage($this, $source);
				break;
		}

		$page->setSource($source);
		return $page;
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	protected function getDefaultModuleList()
	{
		return array(
			'config'   => 'SiteConfigModule',
			'database' => 'SiteDatabaseModule',
			'instance' => 'PinholeMultipleInstanceModule',
		);
	}

	// }}}
}

?>
