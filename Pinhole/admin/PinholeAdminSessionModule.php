<?php

require_once 'Admin/AdminSessionModule.php';

/**
 * Web application module for sessions
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeAdminSessionModule extends AdminSessionModule
{
	// {{{ public function login()

	/**
	 * Logs an admin user into an admin
	 *
	 * @param string $email
	 * @param string $password
	 *
	 * @return boolean true if the admin user was logged in is successfully and
	 *                  false if the admin user could not log in.
	 */
	public function login($email, $password)
	{
		$logged_in = parent::login($email, $password);
		if ($logged_in) {
			$this->user->setDigestHa1(
				$this->app->config->site->auth_realm,
				$email,
				$password);

			$this->user->save();
			echo 'saved new ha1'; exit;
		}

		return $logged_in;
	}

	// }}}
}

?>
