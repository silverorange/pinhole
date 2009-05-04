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
		// TODO: should passphrase be salted?
		$passphrase = md5($passphrase);
		$instance = $this->getInstanceId();

		$sql = sprintf('select instance from InstanceConfigSetting
			where instance %s %s and name = %s and value = %s',
			SwatDB::equalityOperator($instance),
			$this->app->db->quote($instance, 'integer'),
			$this->app->db->quote('pinhole.passphrase', 'text'),
			$this->app->db->quote($passphrase, 'text'));

		$id = SwatDB::queryOne($this->app->db, $sql);

		if ($id !== null) {
			$this->activate();

			if (!isset($this->authenticated_instances) ||
				!($this->authenticated_instances instanceof ArrayObject))
				$this->authenticated_instances = new ArrayObject();

			$this->authenticated_instances[$this->getInstanceId()] = true;

			$logged_in = true;
		} else {
			$this->logout();
			$logged_in = false;
		}

		return $logged_in;
	}

	// }}}
	// {{{ public function logout()

	public function logout()
	{
		unset($this->authenticated_instances[$this->getInstanceId()]);
	}

	// }}}
	// {{{ private function getInstanceId()

	private function getInstanceId()
	{
		$id = ($this->app->hasModule('SiteMultipleInstanceModule')) ?
			$this->app->getInstanceId() : null;

		return ($id === null) ? 0 : $id;
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
		if (!$this->isActive()) {
			return false;
		} elseif (isset($this->authenticated_instances) &&
			array_key_exists($this->getInstanceId(),
				$this->authenticated_instances)) {

			return true;
		} else {
			return false;
		}
	}

	// }}}
}

?>
