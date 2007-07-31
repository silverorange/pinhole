<?php

require_once 'Swat/Swat.php';
require_once 'Site/pages/SitePage.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Pinhole/PinholePhotoFactory.php';

/**
 * Page for indicating when an upload is complete
 *
 * This page is responsible for indicating, via javascript, when the upload is
 * complete. It is the target of the photo upload form.
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoUploadStatus extends SitePage
{
	// {{{ protected properties

	protected $files = array();
	protected $errors = array();

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null)
	{
		$layout = new SiteLayout($app,
			'Pinhole/layouts/xhtml/upload-processor.php');

		parent::__construct($app, $layout);
	}

	// }}}
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

	public function process()
	{
		$photo_factory = new PinholePhotoFactory();
		$photo_factory->setPath(realpath('../'));

		foreach ($_FILES as $id => $file) {
			$saved = $photo_factory->saveUploadedFile('file');

			if (PEAR::isError($saved))
				$this->errors[] = sprintf(
					Pinhole::_('Error uploading file: %s'),
					$_FILES[$id]['name']);
			else
				$this->files = array_merge($saved, $this->files);
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
		$javascript = "var uploaded_files = {\n";

		foreach ($this->files as $filename => $original_filename)
			$javascript.= sprintf("'%s' : '%s',\n",
				$filename, $original_filename);

		$javascript.= "};\n";


		$javascript.= "var upload_errors = [];";

		foreach ($this->errors as $filename)
			$javascript.= sprintf("upload_errors.push('%s');\n",
				$filename);

		foreach ($_FILES as $id => $file)
			$javascript.= sprintf("window.parent.%s_obj.uploadComplete(".
				"uploaded_files, upload_errors);\n", $id);

		return $javascript;
	}

	// }}}
}

?>
