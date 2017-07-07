<?php

/**
 * Page for loading photos
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoLoader extends AdminPage
{
	// {{{ public function __construct()

	public function __construct(
		SiteApplication $app,
		SiteLayout $layout = null,
		array $arguments = array()
	) {
		parent::__construct($app, $layout, $arguments);

		$id = $this->app->initVar('id');
		$dimension = $this->app->initVar('dimension', 'small');

		$this->photo = $this->getPhoto($id);
		$this->dimension_shortname = $dimension;
	}

	// }}}
	// {{{ protected function getPhoto()

	protected function getPhoto($id)
	{
		$sql = sprintf('select PinholePhoto.*
			from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where PinholePhoto.id = %s and ImageSet.instance %s %s',
			$this->app->db->quote($id, 'integer'),
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'));

		$wrapper_class = SwatDBClassMap::get('PinholePhotoWrapper');
		$photos = SwatDB::query($this->app->db, $sql, $wrapper_class);

		if (count($photos) == 0) {
			$instance = $this->app->getInstance();
			if ($instance === null)
				$message = sprintf("Photo with filename '%s' does not exist.",
					$filename);
			else
				$message = sprintf("Photo with filename '%s' does not exist ".
					"in the instance '%s'.", $filename, $instance->shortname);

			throw new AdminNotFoundException($message);
		}

		$photo = $photos->getFirst();
		$photo->setFileBase('../../photos');
		return $photo;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		header('Content-Type: '.$this->photo->getMimeType(
			$this->dimension_shortname));

		readfile($this->photo->getFilePath($this->dimension_shortname));
		exit();
	}

	// }}}
	// {{{ protected function createLayout()

	protected function createLayout()
	{
		return new SiteLayout($this->app, SiteBlankTemplate::class);
	}

	// }}}
}

?>
