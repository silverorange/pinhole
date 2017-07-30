<?php

require_once 'Admin/pages/AdminPage.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'Pinhole/dataobjects/PinholeImageSet.php';
require_once 'Pinhole/dataobjects/PinholePhotoUploadSet.php';
require_once dirname(__FILE__).'/include/PinholePhotoUploader.php';

/**
 * Page for uploading photos
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoUpload extends AdminPage
{
	// {{{ protected properties

	protected $ui_xml = 'Pinhole/admin/components/Photo/upload.xml';
	protected $config_ui_map = array();

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);

		$button = $this->ui->getWidget('submit_button');
		$camera_time_zone = $this->app->config->pinhole->camera_time_zone;

		$time_zone = ($camera_time_zone === null) ?
			$this->app->default_time_zone->getName() : $camera_time_zone;

		$this->ui->getWidget('photo_time_zone')->value = $time_zone;
		$this->ui->getWidget('camera_time_zone')->value = $time_zone;

		$this->ui->getWidget('private_field')->visible =
			$this->app->config->pinhole->enable_private_photos;

		$this->config_ui_map = array(
			'camera_time_zone' => 'photo_time_zone',
			'set_pending' => 'set_pending',
			'set_private_photos' => 'private',
			'set_content_by_meta_data' => 'set_content_by_meta_data',
			'set_tags_by_meta_data' => 'set_tags_by_meta_data',
			'auto_rotate' => 'auto_rotate',
		);

		foreach ($this->config_ui_map as $config_name => $id) {
			$value = $this->app->config->pinhole->$config_name;
			if ($value !== null)
				$this->ui->getWidget($id)->value = $value;
		}
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		$form = $this->ui->getWidget('form');
		$form->process();

		if ($form->isProcessed() && $form->isAuthenticated()) {
			ini_set('memory_limit', -1);
			set_time_limit(300);

			$class_name = SwatDBClassMap::get('PinholePhotoUploadSet');
			$upload_set = new $class_name();
			$upload_set->setDatabase($this->app->db);
			$upload_set->createdate = new SwatDate();
			$upload_set->createdate->toUTC();
			$upload_set->save();

			$files = PinholePhoto::saveUploadedFile('file');
			$image_set = $this->getImageSet();

			foreach ($files as $temp_filename => $original_filename) {
				$photo = $this->getTempPhoto($upload_set, $image_set,
					$temp_filename,	$original_filename);

				$photo->save();
			}

			$this->saveConfigSettings();

			$this->app->relocate($this->getComponentName().'/LastUpload');
		}
	}

	// }}}
	// {{{ protected function getTempPhoto()

	/**
	 * Process a given image file
	 *
	 * @param PinholePhotoUploadSet $upload_set The upload set that photo
	 *                              belongs to
	 * @param SiteImageSet $image_set The image set that photo belongs to
	 * @param string $file The file path to the image file
	 * @param string $original_filename The original name of the file
	 */
	protected function getTempPhoto(
		PinholePhotoUploadSet $upload_set,
		SiteImageSet $image_set,
		$filename,
		$original_filename
	) {
		$class_name = SwatDBClassMap::get('PinholePhoto');
		$photo = new $class_name();
		$photo->setDatabase($this->app->db);
		$photo->image_set = $image_set;
		$photo->upload_set = $upload_set;
		$photo->original_filename = $original_filename;
		$photo->temp_filename = $filename;
		$photo->status = PinholePhoto::STATUS_UNPROCESSED;
		$photo->auto_publish = (!$this->ui->getWidget('set_pending')->value);
		$photo->private = $this->ui->getWidget('private')->value;

		$photo->photo_time_zone =
			$this->ui->getWidget('photo_time_zone')->value;

		$camera_time_zone = $this->ui->getWidget('camera_time_zone')->value;
		if ($camera_time_zone === null)
			$camera_time_zone = $photo->photo_time_zone;

		$photo->camera_time_zone = $camera_time_zone;

		$photo->set_content_by_meta_data =
			$this->ui->getWidget('set_content_by_meta_data')->value;

		$photo->set_tags_by_meta_data =
			$this->ui->getWidget('set_tags_by_meta_data')->value;

		$photo->auto_rotate = $this->ui->getWidget('auto_rotate')->value;

		return $photo;
	}

	// }}}
	// {{{ protected function saveConfigSettings()

	protected function saveConfigSettings()
	{
		$config_paths = array();
		foreach ($this->config_ui_map as $config_name => $id) {
			$value = $this->ui->getWidget($id)->value;
			if (is_bool($value))
				$value = ($value) ? '1' : '0';

			$this->app->config->pinhole->{$config_name} = $value;

			$config_paths[] = 'pinhole.'.$config_name;
		}

		$this->app->config->save($config_paths);
	}

	// }}}
	// {{{ protected function getImageSet()

	protected function getImageSet()
	{
		$class_name = SwatDBClassMap::get('PinholeImageSet');
		$image_set = new $class_name();
		$image_set->setDatabase($this->app->db);
		$image_set->instance = $this->app->getInstance();
		$found = $image_set->loadByShortname('photos');

		if (!$found)
			throw new SwatException(sprintf('Image set “%s” does not exist.',
				$this->image_set_shortname));

		return $image_set;
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->ui->getWidget('form')->action =
			$this->getComponentName().'/Upload';

		$this->ui->getWidget('form')->upload_status_server =
			$this->getComponentName().'/UploadStatusServer';
	}

	// }}}
	// {{{ protected function display()

	protected function display()
	{
		parent::display();
		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		return "new PinholePhotoUploadPage();";
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			'packages/pinhole/admin/javascript/pinhole-photo-upload-page.js',
			Pinhole::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/admin/styles/pinhole-photo-upload-page.css',
			Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
