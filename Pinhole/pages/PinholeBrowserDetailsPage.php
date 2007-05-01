<?php

require_once 'Pinhole/pages/PinholeBrowserPage.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeBrowserDetailsPage extends PinholeBrowserPage
{
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout,
		$tags = null, $photo_id = null)
	{
		parent::__construct($app, $layout, $tags);

		// TODO: use classmap
		$this->photo = new PinholePhoto();
		$this->photo->setDatabase($this->app->db);
		$this->photo->load(intval($photo_id));
	}

	// }}}
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

		$this->layout->startCapture('content');
		$this->displayPhoto();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function displayPhoto()

	protected function displayPhoto()
	{
		$img_tag = new SwatHtmlTag('img');
		// TODO: use dimension objects
		$img_tag->src = $this->photo->getUri('large');
		$img_tag->display();
	}

	// }}}
}

?>
