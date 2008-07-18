<?php

require_once 'Site/SiteSessionModule.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/exceptions/SiteException.php';
require_once 'Site/dataobjects/SiteAccount.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatForm.php';
require_once 'Swat/SwatString.php';

/**
 * Web application module for sessions with passphrases.
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeSessionModule extends SiteSessionModule
{
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * The site account session module depends on the SiteDatabaseModule
	 * feature.
	 *
	 * @return array an array of {@link SiteApplicationModuleDependency}
	 *                        objects defining the features this module
	 *                        depends on.
	 */
	public function depends()
	{
		$depends = parent::depends();
		$depends[] = new SiteApplicationModuleDependency('SiteDatabaseModule');
		return $depends;
	}

	// }}}
	// {{{ public function login()

	/**
	 * Logs the current session into a {@link SiteAccount}
	 *
	 * @param string $email the email address of the account to login.
	 * @param string $password the password of the account to login.
	 *
	 * @return boolean true if the session was successfully logged in and false
	 *                       if the passphrase is incorrect.
	 */
	public function login($passphrase)
	{
		if ($this->isLoggedIn())
			$this->logout();

		$instance = ($this->app->hasModule('SiteMultipleInstanceModule')) ?
			$this->app->getInstanceId() : null;

		// TODO: should passphrase be md5/salted?

		$sql = sprintf('select instance from instanceconfigsetting
			where instance %s %s and name = %s and value = %s',
			SwatDB::equalityOperator($instance),
			$this->app->db->quote($instance, 'integer'),
			$this->app->db->quote('pinhole.passphrase', 'text'),
			$this->app->db->quote($passphrase, 'text'));

		$id = SwatDB::queryOne($this->app->db, $sql);

		if ($id !== null) {
			$this->activate();

			if (!isset($this->authenticated_instances))
				$this->authenticated_instances = array();

			$this->authenticated_instances[] = ($id === null) ? 0 : $id;
		}

		return $this->isLoggedIn();
	}

	// }}}
	// {{{ public function isLoggedIn()

	/**
	 * Checks the current user's logged-in status
	 *
	 * @return boolean true if user is logged in, false if the user is not
	 *                  logged in.
	 */
	public function isLoggedIn()
	{
		$instance = ($this->app->hasModule('SiteMultipleInstanceModule')) ?
			$this->app->getInstanceId() : 0;

		if (!$this->isActive())
			return false;
		elseif (!$this->app->isSecure())
			return false;
		elseif (isset($this->authenticated_instances) &&
			in_array($instance, $this->authenticated_instances))
			return true;
		else
			return false;
	}

	// }}}
}

?>
