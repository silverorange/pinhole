<?php

require_once 'Swat/Swat.php';
require_once 'Site/pages/SitePage.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'Pinhole/layouts/PinholePhotoUploadProcessorLayout.php';

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
 * @todo      throw not found exception if there is no post data
 */
class PinholePhotoUploadProcessor extends SitePage
{
	// {{{ public function process()

	/**
	 * Processes uploaded photo files
	 */
	public function process()
	{
		foreach ($_FILES as $file) {
		}
	}

	// }}}
	// {{{ public function build()

	/**
	 * Builds the layout content of this upload processor
	 *
	 * This displays the required inline JavaScript to mark this file upload
	 * as complete.
	 *
	 * @see PinholePhotoUploadProcessor::getInlineJavaScript()
	 */
	public function build()
	{
		$this->layout->startCapture('content');

		Swat::printObject($_POST);
		Swat::printObject($_FILES);
		Swat::displayInlineJavaScript($this->getInlineJavaScript());

		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	/**
	 * Gets inline JavaScript that marks this file upload as complete
	 */
	protected function getInlineJavaScript()
	{
		$javascript = '';
		foreach ($_FILES as $id => $file)
			$javascript.= sprintf("window.parent.%s_obj.complete();\n", $id);

		return $javascript;
	}

	// }}}
	// {{{ protected function createLayout()

	protected function createLayout()
	{
		return new PinholePhotoUploadProcessorLayout($this->app);
	}

	// }}}
}

?>
