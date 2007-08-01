<?php

require_once 'Admin/pages/AdminDBDelete.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/AdminListDependency.php';

/**
 * Delete confirmation page for Tags
 *
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeTagDelete extends AdminDBDelete
{
	// {{{ private properties

	// used for custom relocate
	private $parent_id;

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$sql = sprintf('select parent from PinholeTag where id = %s',
			$this->app->db->quote($this->getFirstItem(), 'integer'));

		$this->parent_id = SwatDB::queryOne($this->app->db, $sql);

		$sql = 'delete from PinholeTag where id in (%s) and instance = %s';
		$item_list = $this->getItemList('integer');
		$instance = $this->app->instance->getInstance();
		$sql = sprintf($sql, $item_list,
			$this->app->db->quote($instance->id, 'integer'));

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(Pinhole::ngettext(
			'One tag has been deleted.', '%d tags have been deleted.',
			$num), SwatString::numberFormat($num)), SwatMessage::NOTIFICATION);

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function relocate()

	/**
	 * Relocate after process
	 */
	protected function relocate()
	{
		if ($this->single_delete) {
			if ($this->parent_id === null)
				$this->app->relocate('Tag/Index');
			else
				$this->app->relocate(sprintf('Tag/Details?id=%s',
					$this->parent_id));

		} else {
			parent::relocate();
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$item_list = $this->getItemList('integer');
		$instance = $this->app->instance->getInstance();

		$where_clause = sprintf('id in (%s) and instance = %s',
			$item_list,
			$this->app->db->quote($instance->id, 'integer'));
		
		$dep = new AdminListDependency();
		$dep->setTitle(Pinhole::_('tag'), Pinhole::_('tags'));
		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'PinholeTag', 'integer:id', null, 'text:title', 'title',
			$where_clause, AdminDependency::DELETE);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();

		$this->buildNavBar();
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$this->navbar->popEntry();

		if ($this->single_delete) {
			$navbar_rs = SwatDB::executePinholedProc($this->app->db, 
				'getPinholeTagNavBar', array($this->getFirstItem()));

			foreach ($navbar_rs as $elem)
				$this->navbar->addEntry(new SwatNavBarEntry($elem->title,
					'Tag/Details?id='.$elem->id));
		}

		$this->navbar->addEntry(new SwatNavBarEntry(Pinhole::_('Delete')));
	}

	// }}}
}

?>
