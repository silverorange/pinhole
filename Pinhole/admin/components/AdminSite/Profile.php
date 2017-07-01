<?php

/**
 * Edit page for the current admin user profile
 *
 * @package   Pinhole
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @copyright 2009 silverorange
 */
class PinholeAdminSiteProfile extends AdminAdminSiteProfile
{
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		parent::saveDBData();

		$password = $this->ui->getWidget('new_password');
		if ($password->value !== null) {
			$this->app->session->user->setDigestHa1(
				$this->app->config->site->auth_realm,
				$this->app->session->user->email,
				$password);

			$this->app->session->user->save();
		}
	}

	// }}}
}

?>
