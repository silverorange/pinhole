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
	 * Checks if a user is authenticated
	 *
	 * After a user's username and password have been verified, perform
	 * additional hecks on the user's authentification. This method is run
	 * on every page load, not just at login, to ensure the user has
	 * permission to access the admin.
	 *
	 * @return boolean True if the user has authenticated access to the
	 *                 admin. 
	 */
	public function isAuthenticated(AdminApplication $app)
	{
		$authenticated = parent::isAuthenticated($app);

		return ($authenticated &&
			in_array($app->instance->getInstance(),
				$this->instances->getArray()));
	}

	// }}}
	// {{{ protected function loadInstances()

	/**
	 * Load the PinholeInstances that this user has access to
	 *
	 * @return PinholeInstanceWrapper Accessible instances.
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
