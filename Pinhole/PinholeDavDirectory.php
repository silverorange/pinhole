<?php

/**
 * SabreDav WebDAV directory adapter for a PinholePhotoWrapper
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeDavDirectory implements Sabre_DAV_IDirectory
{
	// {{{ protected properties

	/**
	 * @var SiteWebApplication
	 */
	protected $app;

	/**
	 * @var PinholePhotoWrapper
	 */
	protected $photos;

	/**
	 * @var User
	 */
	protected $user;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new WebDAV directory adapter for a list of photos
	 *
	 * @param SiteWebApplication  $app    the application.
	 * @param PinholePhotoWrapper $photos the list of photos in this directory.
	 */
	public function __construct(SiteWebApplication $app, AdminUser $user)
	{
		$this->app     = $app;
		$this->user = $user;
		$this->photos  = $this->getPhotos();
	}

	// }}}
	// {{{ public function createFile()

	/**
	 * Creates a new file in this directory
	 *
	 * @param string   $name the name of the file to create.
	 * @param resource $data optional. The initial data payload. If null,
	 *                       an empty file is created.
	 */
	public function createFile($original_filename, $data = null)
	{
		$parts = explode('.', $original_filename);
		$ext = strtolower(end($parts));
		$filename = uniqid('file').'.'.$ext;

		$file_path = sprintf('%s/%s',
			sys_get_temp_dir(), $filename);

		file_put_contents($file_path, $data);

		$files = PinholePhoto::parseFile($file_path, $original_filename);

		$image_set = $this->getImageSet();
		$now = new SwatDate();
		$now->toUTC();

		foreach ($files as $temp_filename => $original_filename) {
			$class_name = SwatDBClassMap::get('PinholePhoto');
			$photo = new $class_name();
			$photo->setDatabase($this->app->db);
			$photo->image_set = $image_set->id;
			//$photo->upload_set = $upload_set;
			$photo->original_filename = $original_filename;
			$photo->temp_filename = $temp_filename;
			$photo->status = PinholePhoto::STATUS_UNPROCESSED;
			$photo->dav_upload = true;
			$photo->upload_date = $now;

			$config = $this->app->config->pinhole;

			$photo->auto_publish     = (!$config->dav_set_pending);
			$photo->private          = $config->dav_set_private_photos;
			$photo->photo_time_zone  = $config->dav_photo_time_zone;
			$photo->camera_time_zone = $config->dav_photo_time_zone;
			$photo->auto_rotate      = $config->dav_auto_rotate;

			$photo->set_content_by_meta_data =
				$config->dav_set_content_by_meta_data;

			$photo->set_tags_by_meta_data =
				$config->dav_set_tags_by_meta_data;

			$photo->save();

			$photo->setFileBase('../photos');

			$processor = new PinholePhotoProcessor($this->app);
			$processor->processPhoto($photo);

			$photo->save();
		}
	}

	// }}}
	// {{{ public function createDirectory()

	public function createDirectory($name)
	{
		throw new Sabre_DAV_Exception_NotImplemented(
			'Creating directories is not allowed on Pinhole.');
	}

	// }}}
	// {{{ public function getChild()

	/**
	 * Gets a specific file in this directory by its name
	 *
	 * @param string $name the name of the file to get.
	 *
	 * @return Sabre_DAV_INode the file.
	 *
	 * @throws Sabre_DAV_Exception_FileNotFound if no such file exists in this
	 *                                          directory.
	 */
	public function getChild($name)
	{
		$file = null;

		foreach ($this->photos as $photo) {
			if ($photo->original_filename == $name) {
				$file = new PinholeDavFile($this->app, $photo);
			}
		}

		if ($file === null) {
			throw new Sabre_DAV_Exception_FileNotFound(
				'Photo with name "'.$name.'" does not exist.');
		}

		return $file;
	}

	// }}}
	// {{{ public function getChildren()

	public function getChildren()
	{
		$files = array();

		foreach ($this->photos as $photo) {
			$files[] = new PinholeDavFile($this->app, $photo);
		}

		return $files;
	}

	// }}}
	// {{{ public function getName()

	public function getName()
	{
		return '/';
	}

	// }}}
	// {{{ public function setName()

	public function setName($name)
	{
		// can not rename root, do nothing
	}

	// }}}
	// {{{ public function getLastModified()

	public function getLastModified()
	{
		$time = 0;

		foreach ($this->photos as $photo) {
			$time = max($photo->upload_date->getTimestamp(), $time);
		}

		if ($time == 0) {
			$date = new SwatDate();
			$time = $date->getTimestamp();
		}

		return $time;
	}

	// }}}
	// {{{ public function delete()

	/**
	 * Deletes this directory
	 *
	 * @return void
	 */
	public function delete()
	{
		throw new Sabre_DAV_Exception_NotImplemented(
			'Deleting directories is not allowed on ClusterPinhole.');
	}

	// }}}
	// {{{ protected function getImageSet()

	protected function getImageSet()
	{
		$class_name = SwatDBClassMap::get('PinholeImageSet');
		$image_set = new $class_name();
		$image_set->setDatabase($this->app->db);
		$image_set->instance = $this->app->getInstance();

		if (!$image_set->loadByShortname('photos')) {
			throw new SwatException('Image set “photos” does not exist.');
		}

		return $image_set;
	}

	// }}}
	// {{{ protected function getPhotos()

	protected function getPhotos()
	{
		// load the photos uploaded in the last day
		$date = new SwatDate();
		$date->subtractDays(1);
		$date->toUTC();

		$instance_id = ($this->app->getInstance() === null) ?
			null : $this->app->getInstanceId();

		$sql = sprintf(
			'select PinholePhoto.* from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where ImageSet.instance %s %s and PinholePhoto.dav_upload = %s
				and PinholePhoto.upload_date > %s
			order by original_filename asc',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote($date->getDate(), 'date')
		);

		$photos = SwatDB::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('PinholeSimplePhotoWrapper')
		);

		return $photos;
	}

	// }}}
}

?>
