<?php

require_once 'Pinhole/PinholeMultipleInstanceModule.php';
require_once 'Pinhole/dataobjects/PinholeAdminUser.php';
require_once 'Admin/AdminApplication.php';

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
		// hard coded until module dependencies are automatic
		$modules = array(
			'cookie'   => 'SiteCookieModule',
			'database' => 'SiteDatabaseModule',
			'instance' => 'PinholeMultipleInstanceModule',
			'session'  => 'AdminSessionModule',
			'messages' => 'SiteMessagesModule',
			'config'   => 'SiteConfigModule',
		);

		/*
		$modules = parent::getDefaultModuleList();
		$modules['instance'] = 'PinholeMultipleInstanceModule'; 
		*/

		return $modules;
	}

	// }}}
}

?>
