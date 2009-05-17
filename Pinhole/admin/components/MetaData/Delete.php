<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';

/**
 * Delete confirmation page for PinholeMetaData component
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeMetaDataDelete extends AdminDBDelete
{
	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$instance_id = $this->app->getInstanceId();

		$sql = 'delete from PinholeMetaData where id in (%s)
			and instance %s %s';

		$item_list = $this->getItemList('integer');
		$sql = sprintf($sql, $item_list,
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(Pinhole::ngettext(
			'One metadata section has been deleted.',
			'%d metadata sections have been deleted.', $num),
			SwatString::numberFormat($num)),
			SwatMessage::NOTIFICATION);

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	public function buildInternal()
	{
		parent::buildInternal();

		$item_list = $this->getItemList('integer');
		$instance_id = $this->app->getInstanceId();

		$where_clause = sprintf('id in (%s) and instance %s %s',
			$item_list,
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$dep = new AdminListDependency();
		$dep->setTitle(Pinhole::_('metadata section'),
			Pinhole::_('metadata sections'));

		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'PinholeMetaData', 'integer:id', null, 'text:title', 'title',
			$where_clause, AdminDependency::DELETE);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';
	}

	// }}}
}

?>
