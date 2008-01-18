<?php

require_once 'Pinhole/pages/PinholePage.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Swat/exceptions/SwatException.php';
require_once 'Site/exceptions/SiteNotFoundException.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
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
		$instance_id = $this->app->instance->getId();
		$where_clause = sprintf(
			'PinholePhoto.filename = %s and '.
			'PinholePhoto.instance %s %s',
			$this->app->db->quote($filename, 'text'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$photos = PinholePhotoWrapper::loadSetFromDBWithDimension(
			$this->app->db, $dimension, $where_clause);

		if (count($photos) == 0) {
			// TODO: make this exception work better with null instances
			throw new SiteNotFoundException(sprintf("Photo with ".
				"filename '%s' does not exist in the instance '%s'.",
				$filename, $this->app->instance-getInstance()->shortname));
		}

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
		readfile($this->photo->getDimension(
			$this->dimension_shortname)->getPath());

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
