<?php

require_once 'Site/SiteMultipleInstanceModule.php';
require_once 'Admin/AdminApplication.php';
require_once 'Pinhole/dataobjects/PinholeAdminUser.php';

/**
 * Web application class for an administering Pinhole
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeAdminApplication extends AdminApplication
{
	// {{{ protected function getDefaultModuleList()

	/**
	 * Gets the list of default modules to load for this applicaiton
	 *
	 * @return array
	 * @see    AdminApplication::getDefaultModuleList()
	 */
	protected function getDefaultModuleList()
	{
		$modules = parent::getDefaultModuleList();
		$modules['instance'] = 'SiteMultipleInstanceModule';

		return $modules;
	}

	// }}}
}

?>
