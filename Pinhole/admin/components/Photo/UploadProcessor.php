<?php

require_once 'Admin/pages/AdminPage.php';
require_once 'Pinhole/dataobjects/Photo.php';

/**
 * Page for processing uploaded photos
 *
 * This page is responsible for and decompressing, resizing, cropping and
 * database insertion required for new photos. It is the target of the
 * photo upload form.
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @todo      use custom layout to only display javascript
 * @todo      throw not found exception if there is no post data
 */
class PinholePhotoUploadProcessor extends AdminPage
{
	public function __construct()
	{
		$this->processFiles();
		$this->display();
	}

	protected function processFiles()
	{
		foreach ($_FILES as $file) {
		}
	}

	protected function display()
	{
		Swat::printObject($_POST);
		Swat::printObject($_FILES);
		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	protected function getInlineJavaScript()
	{
		$javascript = '';
		foreach ($_FILES as $id => $file)
			$javascript.= sprintf("window.parent.%s_obj.complete();\n", $id);

		return $javascript;
	}
}

?>
