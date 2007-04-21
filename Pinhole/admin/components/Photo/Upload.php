<?php

require_once 'Admin/pages/AdminPage.php';
require_once 'Pinhole/admin/components/Photo/include/PinholePhotoUploader.php';

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
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
	}

	// }}}
}

?>
