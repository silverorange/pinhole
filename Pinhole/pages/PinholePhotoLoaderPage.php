<?php

require_once 'Pinhole/pages/PinholePage.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholePhotoLoaderPage extends PinholePage
{
	// {{{ protected properties

	protected $dimension_shortname;
	protected $photoid;

	// }}}

	// init phase
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout,
		$dimension_shortname, $photoid)
	{
		parent::__construct($app, $layout);

		$this->dimension_shortname = $dimension_shortname;
		$this->photoid = $photoid;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		// TODO: add security, make sure photo exists, obviously don't build the
		// path like this

		$mime_type = 'image/jpeg';

		header('Content-Type: '.$mime_type);
		readfile('../private-photos/'.$this->dimension_shortname.'/'.$this->photoid.'.jpg');	

		exit();
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
