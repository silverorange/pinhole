<?php

/**
 * An admin user
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeAdminUser extends AdminUser
{
	// {{{ public properties

	/**
	 * Hashed value HA1 for HTTP Digest
	 *
	 * md5(USERNAME:REALM:PASS)
	 *
	 * @var string
	 */
	public $digest_ha1;

	// }}}
	// {{{ public function setDigestHa1()

	public function setDigestHa1($realm, $username, $password)
	{
		$this->digest_ha1 = md5(
			sprintf(
				'%s:%s:%s',
				$username,
				$realm,
				$password
			)
		);
	}

	// }}}
}

?>
