<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';

/**
 * Delete confirmation page for PinholePhotographer component
 *
 * @package   Pinhole
 * @copyright 2005-2006 silverorange
 */
class PinholePhotographerDelete extends AdminDBDelete
{
	// process phase
	// {{{ protected function processDBData

	protected function processDBData()
	{
		parent::processDBData();

		$sql = 'delete from PinholePhotographer where id in (%s)';
		$item_list = $this->getItemList('integer');
		$sql = sprintf($sql, $item_list);
		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(Admin::ngettext(
			'One photographer has been deleted.',
			'%d photographers have been deleted.', $num),
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

		$dep = new AdminListDependency();
		$dep->setTitle(Admin::_('pinhole photographer'), Admin::_('pinhole photographers'));
		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'PinholePhotographer', 'integer:id', null, 'text:fullname', 'fullname',
			'id in ('.$item_list.')', AdminDependency::DELETE);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';
	}

	// }}}
}

?>