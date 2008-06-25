<?php

require_once 'Admin/pages/AdminPage.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'include/PinholePhotoUploader.php';

/**
 * Page for uploading photos
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @todo      When there is no JavaScript, this page is responsible for
 *            processing photos. It should use UploadProcessor to achieve this.
 */
class PinholePhotoUpload extends AdminPage
{
	// {{{ protected properties

	protected $ui_xml = 'Pinhole/admin/components/Photo/upload.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
		$button = $this->ui->getWidget('submit_button');
		$button->sensitive = false;

		$this->ui->getWidget('photo_time_zone')->value =
			$this->app->default_time_zone->getID();

		$this->ui->getWidget('camera_time_zone')->value =
			$this->app->default_time_zone->getID();
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		$form = $this->ui->getWidget('time_zone_form');
		$form->process();

		if ($form->isProcessed() && (isset($_POST['number_of_photos']))) {
			$number_of_photos = $_POST['number_of_photos'];
			$counter = 0;

			while ($counter < $number_of_photos) {
				$counter ++;
				$field_name = sprintf('photo_id%s', $counter);
				$photo_id = $_POST[$field_name];
				$this->processTimeZone($photo_id);
			}

			$this->app->replacePage('Photo/Pending');
		}
	}

	// }}}
	// {{{ protected function processTimeZone()

	protected function processTimeZone($photo_id)
	{
		$class_name = SwatDBClassMap::get('PinholePhoto');
		$photo = new $class_name();
		$photo->setDatabase($this->app->db);

		$instance_id = $this->app->getInstanceId();

		if (!$photo->load($photo_id)) {
			throw new AdminNotFoundException(
				sprintf(Pinhole::_('Photo with id “%s” not found.'),
				$this->id));
		} elseif ($photo->image_set->instance !== null &&
			$photo->image_set->instance->id != $instance_id) {
			throw new AdminNotFoundException(
				sprintf(Pinhole::_('Photo with id “%s” loaded '.
					'in the wrong instance.'),
				$photo->id));
		}

		// save the photo time zone
		$photo->photo_time_zone = $this->ui->getWidget('photo_time_zone')->value;

		// convert the photo date to UTC using the camera time zone
		$photo->photo_date = new SwatDate($photo->photo_date);
		$camera_time_zone = $this->ui->getWidget('camera_time_zone')->value;
		$photo->photo_date->setTZbyID($camera_time_zone);
		$photo->photo_date->toUTC();

		$photo->save();
	}

	// }}}

	// build phase
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
		$uploader_object = 'file_obj';
		return sprintf("var page = new PinholePhotoUploadPage(%s);",
			$uploader_object);
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
	}

	// }}}
}

?>
