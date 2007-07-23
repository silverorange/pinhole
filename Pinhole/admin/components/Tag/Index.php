<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Pinhole/dataobjects/PinholeTag.php';

/**
 * Search page for Tags
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeTagIndex extends AdminSearch
{
	// {{{ protected properties

	protected $where_clause;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->mapClassPrefixToPath('Pinhole', 'Pinhole');
		$this->ui->loadFromXML(dirname(__FILE__).'/index.xml');
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$pager = $this->ui->getWidget('pager');
		$pager->process();

		if ($pager->getCurrentPage() > 0) {
			$disclosure = $this->ui->getWidget('search_disclosure');
			$disclosure->open = false;
		}
	}

	// }}}
	// {{{ protected function processActions()

	/**
	 * Processes photographer actions
	 *
	 * @param SwatTableView $view the table-view to get selected tags
	 *                             from.
	 * @param SwatActions $actions the actions list widget.
	 */
	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Tag/Delete');
			$this->app->getPage()->setItems($view->checked_items);
			break;
		case 'status_action':
			$num = count($view->checked_items);

			$status = $this->ui->getWidget('status_flydown')->value;

			SwatDB::updateColumn($this->app->db, 'PinholeTag',
				'integer:status', $status,
				'id', $view->checked_items);

			$message = new SwatMessage(sprintf(Pinhole::ngettext(
				'The status of one tag has been changed.',
				'The status of %s tags has been changed.', $num),
				SwatString::numberFormat($num)));

			$this->app->messages->add($message);
			break;
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$rs = SwatDB::executeStoredProc($this->app->db, 'getPinholeTagTree', array('null'));
		$tree = SwatDB::getDataTree($rs, 'title', 'id', 'levelnum');
		$this->ui->getWidget('search_parent')->setTree($tree);

		$this->ui->getWidget('status_flydown')->addOptionsByArray(
			PinholeTag::getStatuses());
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		if ($this->where_clause === null) {
			$where = 'PinholeTag.name_space is null';

			$clause = new AdminSearchClause('title');
			$clause->table = 'PinholeTag';
			$clause->value = $this->ui->getWidget('search_title')->value;
			$clause->operator = $this->ui->getWidget('search_title_operator')->value;
			$where.= $clause->getClause($this->app->db);

			$clause = new AdminSearchClause('id');
			$clause->table = 'PinholeTag';
			$clause->value = $this->ui->getWidget('search_parent')->value;
			$clause->operator = AdminSearchClause::OP_EQUALS;
			$where.= $clause->getClause($this->app->db);

			$this->where_clause = $where;
		}

		return $this->where_clause;
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = sprintf('select count(id) from PinholeTag where %s',
			$this->getWhereClause());

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = 'select PinholeTag.id,
					PinholeTag.title, 
					PinholeTag.shortname,
					PinholeTag.status
				from PinholeTag
				where %s
				order by %s';

		$sql = sprintf($sql,
			$this->getWhereClause(),
			$this->getOrderByClause($view, 'PinholeTag.title'));

		$this->app->db->setLimit($pager->page_size, $pager->current_record);
		$rs = SwatDB::query($this->app->db, $sql);

		$this->ui->getWidget('results_frame')->visible = true;

		if (count($rs) > 0)
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage(Pinhole::_('result'), 
					Pinhole::_('results'));

		foreach ($rs as $row)
			$row->status_title = PinholeTag::getStatusTitle($row->status);

		return $rs;
	}

	// }}}
}

?>
