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
		$submit_timezones = $this->ui->getWidget('submit_timezones');
		$submit_timezones->sensitive = false;
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		$form_field = $this->ui->getWidget('timezone_field');
		$form_field->process();

		if ($form_field->isProcessed() && (isset($_POST['number_of_photos']))) {
			$photo_timezone  = $this->ui->getWidget('photo_timezone');
			$camera_timezone = $this->ui->getWidget('camera_timezone');
			$number_of_photos = $_POST['number_of_photos'];
			$counter = 0;

			$photo_timezone_value  = $photo_timezone->value;
			$camera_timezone_value = $camera_timezone->value;

			if ($number_of_photos > 1){
				while ($counter < $number_of_photos) {
					$counter ++;
					$id = $_POST['photo_id'.$counter];
					$photo = new PinholePhoto();
					$photo->setDatabase($this->app->db);
					$photo->load(intval($id));
	
					$photo->photo_time_zone = $photo_timezone_value;
					$photo->save();
				}
			} else {
				$id = $_POST['photo_id1'];
				$photo = new PinholePhoto();
				$photo->setDatabase($this->app->db);
				$photo->load(intval($id));

				$photo->photo_time_zone = $photo_timezone_value;
				$photo->save();
			}
			
			$this->app->replacePage('Photo/Pending');
		}
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
