<?php

require_once 'Site/pages/SitePage.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePage extends SitePage
{
	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->header_content = '';
		$this->layout->sidbar_content = '';
	}

	// }}}
}

?>
