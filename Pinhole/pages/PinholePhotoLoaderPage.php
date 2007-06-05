<?php

require_once 'Pinhole/pages/PinholePage.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
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
		$where_clause = sprintf('PinholePhoto.filename = %s',
			$this->app->db->quote($filename, 'text'));

		$photos = PinholePhotoWrapper::loadSetFromDBWithDimension(
			$this->app->db, $dimension, $where_clause);

		if ($photos === null)
			throw SiteNotFoundException(sprintf('Photo with
				filename %s does not exist',
				$filename));

		return $photos->getFirst();
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$mime_type = 'image/jpeg';

		header('Content-Type: '.$mime_type);
		readfile($this->photo->getDimension($this->dimension_shortname)->getPath());	

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
