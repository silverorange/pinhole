<?php

require_once 'Admin/components/AdminUser/Edit.php';

/**
 * Edit page for AdminUsers component
 *
 * @package   Pinhole
 * @copyright 2006-2007 silverorange
 */
class PinholeAdminUserEdit extends AdminAdminUserEdit
{
	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$password = $this->ui->getWidget('password');
		if ($password->value !== null) {
			$this->user->setDigestHa1(
				$this->app->config->site->auth_realm,
				$this->ui->getWidget('email')->value,
				$password);
		}

		parent::saveDBData();
	}

	// }}}
}

?>
