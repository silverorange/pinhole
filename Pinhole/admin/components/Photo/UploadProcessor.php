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
 */
class PinholePhotoUploadProcessor extends SitePage
{
	// {{{ public function init()

	/**
	 * Makes sure this page was loaded in a file upload context
	 *
	 * @throws AdminNotFoundException if this page was not loaded from a file
	 *                                upload context.
	 */
	public function init()
	{
		if (!isset($_FILES))
			throw new AdminNotFoundException(Pinhole::_('Page not found.'));
	}

	// }}}
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
