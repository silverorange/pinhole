<?php

require_once 'Swat/SwatNavBar.php';
require_once 'Site/layouts/SiteLayout.php';

/**
 * Layout for Gallery pages
 *
 * @package   Gallery 
 * @copyright 2007 silverorange
 */
class GalleryLayout extends SiteLayout
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

		$this->addHtmlHeadEntry(
			new SwatStyleSheetHtmlHeadEntry('styles/gallery-layout.css'));

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
