<?php

/**
 * Admin front-page that redirects to photos tool
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeFrontIndex extends AdminPage
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		// relocate to photo index page
		$this->app->relocate('Photo');
	}

	// }}}
}

?>
