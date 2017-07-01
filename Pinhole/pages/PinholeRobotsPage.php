<?php

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeRobotsPage extends SitePage
{
	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		if (!$this->app->config->pinhole->search_engine_indexable) {
			$this->displayDisallowString();
		} else {
			$this->displayGeneralRules();
		}

		exit();
	}

	// }}}
	// {{{ protected function displayDisallowString()

	protected function displayDisallowString()
	{
		echo "User-agent: * \nDisallow: /";
	}

	// }}}
	// {{{ protected function displayGeneralRules()

	protected function displayGeneralRules()
	{
		echo "User-agent: * \n";
		echo "Disallow: /tag?*\n";
		echo "Allow: /tag?page.number=*\n";
		echo "Crawl-delay: 60";
	}

	// }}}
	// {{{ protected function createLayout()

	protected function createLayout()
	{
		return new SiteLayout($this->app,
			'../layouts/xhtml/blank.php');
	}

	// }}}
}

?>
