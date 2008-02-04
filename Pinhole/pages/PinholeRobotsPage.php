<?php

require_once 'Site/pages/SitePage.php';

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

		echo $this->getDisallowString();

		exit();
	}

	// }}}
	// {{{ protected function getDisallowString()

	protected function getDisallowString()
	{
		//return '';
		return "User-agent: * \nDisallow: /";
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
