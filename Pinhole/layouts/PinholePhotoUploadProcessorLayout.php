<?php

/**
 * Layout for admin photo upload processor
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoUploadProcessorLayout extends SiteLayout
{
	// {{{ public function __construct()

	public function __construct($app, $filename = null)
	{
		parent::__construct($app, 'Pinhole/layouts/xhtml/upload-processor.php');
	}

	// }}}
}

?>
