<?php

require_once 'Admin/dataobjects/AdminUser.php';
require_once 'Pinhole/dataobjects/PinholeInstanceWrapper.php';

/**
 * User account for the admin
 *
 * @package   Pinhole
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeAdminUser extends AdminUser
{
	// {{{ public function isAuthenticated()

	/**
	 * Checks if a user is authenticated for an admin application
	 *
	 * After a user's username and password have been verified, perform
	 * additional checks on the user's authentication. This method should be
	 * checked on every page load -- not just at login -- to ensure the user
	 * has permission to access the specified admin application.
	 *
	 * Pinhole checks whether or not this user belongs to the current site
	 * instance as well as performing all regular checks.
	 *
	 * @param AdminApplication $app the application to authenticate this user
	 *                               against.
	 *
	 * @return boolean true if this user has authenticated access to the
	 *                 admin and false if this user does not.
	 */
	public function isAuthenticated(AdminApplication $app)
	{
		$authenticated = parent::isAuthenticated($app);

		if ($authenticated &&
			!isset($this->instances[$app->instance->getInstance()->id]))
			$authenticated = false;

		return $authenticated;
	}

	// }}}
	// {{{ protected function loadInstances()

	/**
	 * Load the PinholeInstances that this user has access to
	 *
	 * @return PinholeInstanceWrapper the site instances this user belongs to.
	 */
	protected function loadInstances()
	{
		$sql = sprintf('select PinholeInstance.*
				from PinholeInstance
				inner join AdminUserInstanceBinding on
					AdminUserInstanceBinding.instance = PinholeInstance.id
				where AdminUserInstanceBinding.usernum = %s',
			$this->db->quote($this->id, 'integer'));

		$wrapper_class = SwatDBClassMap::get('PinholeInstanceWrapper');
		return SwatDB::query($this->db, $sql, $wrapper_class);
	}

	// }}}
}

?>
