<?php

require_once 'Pinhole/Pinhole.php';
require_once 'Swat/SwatNavBar.php';
require_once 'Site/layouts/SiteLayout.php';

/**
 * Layout for pages in the Pinhole photo gallery package
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeLayout extends SiteLayout
{
	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();
		$this->data->header_content = '';
		$this->data->sidebar_content = '';
		$this->data->content = '';
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/styles/pinhole-layout.css',
			Pinhole::PACKAGE_ID));
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->data->html_title = $this->data->title.
			((strlen($this->data->title) > 0) ? ' - ' : '').
			'Gallery';
			/* TODO: make this gallery name dynamic  */
	}

	// }}}
}

?>
