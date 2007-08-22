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
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->ui_xml = dirname(__FILE__).'/admin-user-edit.xml';

		parent::initInternal();

		$instance_list = $this->ui->getWidget('instances');
		$instance_list_options = SwatDB::getOptionArray($this->app->db,
			'PinholeInstance', 'title', 'id', 'title');
		$instance_list->addOptionsByArray($instance_list_options);		
	}

	// }}}

	// process phase
	// {{{ protected function saveBindingTables()

	protected function saveBindingTables()
	{
		parent::saveBindingTables();

		$instance_list = $this->ui->getWidget('instances');

		SwatDB::updateBinding($this->app->db, 'AdminUserInstanceBinding',
			'usernum', $this->user->id, 'instance', $instance_list->values,
			'PinholeInstance', 'id');
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		parent::loadDBData();

		$instance_list = $this->ui->getWidget('instances');
		$instance_list->values = SwatDB::queryColumn($this->app->db,
			'AdminUserInstanceBinding', 'instance', 'usernum', $this->id);
	}

	// }}}
}

?>
