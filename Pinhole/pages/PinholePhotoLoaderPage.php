<?php

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoLoaderPage extends SitePage
{
	// {{{ protected properties

	protected $photo;
	protected $dimension_shortname;

	// }}}

	// init phase
	// {{{ public function __construct()

	public function __construct(
		SiteApplication $app,
		SiteLayout $layout,
		array $arguments
	) {
		parent::__construct($app, $layout, $arguments);

		$this->photo = $this->getPhoto($this->getArgument('filename'));
		$this->dimension_shortname = $this->getArgument('dimension_shortname');
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'dimension_shortname' => array(0, null),
			'filename' => array(1, null),
		);
	}

	// }}}
	// {{{ protected function getPhoto()

	protected function getPhoto($filename)
	{
		$sql = sprintf('select PinholePhoto.*
			from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where PinholePhoto.filename = %s and ImageSet.instance %s %s',
			$this->app->db->quote($filename, 'text'),
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

			throw new SiteNotFoundException($message);
		}

		$photo = $photos->getFirst();

		if ($photo->private && !$this->app->session->isLoggedIn()) {
			$message = sprintf("Photo with filename '%s' is private and user ".
				"is not logged in.", $filename);

			throw new SiteNotFoundException($message);
		}

		$photo->setFileBase('../photos');
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
