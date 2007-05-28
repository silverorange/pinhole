<?php

require_once 'Pinhole/pages/PinholePage.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'Swat/exceptions/SwatException.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholePhotoLoaderPage extends PinholePage
{
	// {{{ protected properties

	protected $photo;
	protected $dimension_shortname;

	// }}}

	// init phase
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout,
		$dimension_shortname, $photo_id)
	{
		parent::__construct($app, $layout);

		$this->photo = $this->getPhoto($photo_id, $dimension_shortname);
		$this->dimension_shortname = $dimension_shortname;
	}

	// }}}
	// {{{ protected function getPhoto()

	protected function getPhoto($filename, $dimension)
	{
		$photo = new PinholePhoto();
		$photo->setDatabase($this->app->db);
		$found = $photo->loadFromFilename($filename, $dimension);

		if ($found === false)
			throw SiteNotFoundException(sprintf('Photo with
				filename %s does not exist',
				$filename));

		return $photo;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$mime_type = 'image/jpeg';

		if (!ctype_alnum($this->dimension_shortname))
			throw new SwatException(sprintf('Dimension "%s"
				must be alpha-numeric.'),
				$this->dimension_shortname);

		header('Content-Type: '.$mime_type);
		readfile('../private-photos/'.$this->dimension_shortname.
				'/'.$this->photo->filename.'.jpg');	

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
