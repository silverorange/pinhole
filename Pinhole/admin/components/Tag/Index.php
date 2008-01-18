<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'Swat/SwatTableStore.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Pinhole/dataobjects/PinholeTagDataObject.php';
require_once 'Pinhole/dataobjects/PinholeTagDataObjectWrapper.php';
require_once 'Pinhole/tags/PinholeTag.php';

/**
 * Search page for tags
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
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		if ($this->where_clause === null) {
			$instance = $this->app->instance->getInstance();

			$this->where_clause = sprintf('PinholeTag.instance %s %s',
				$this->app->db->equalityOperator($instance->getId()),
				$this->app->db->quote($instance->getId(), 'integer'));

			$clause = new AdminSearchClause('title');
			$clause->table = 'PinholeTag';
			$clause->value = $this->ui->getWidget('search_title')->value;
			$clause->operator = $this->ui->getWidget('search_title_operator')->value;
			$this->where_clause.= $clause->getClause($this->app->db, 'and');
		}

		return $this->where_clause;
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$store = new SwatTableStore();

		$sql = 'select count(id) from PinholeTag';
		$where_clause = $this->getWhereClause();
		if (strlen($where_clause) > 0)
			$sql.= ' where '.$this->getWhereClause();

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = 'select PinholeTag.* from PinholeTag';
		$where_clause = $this->getWhereClause();
		if (strlen($where_clause) > 0)
			$sql.= ' where '.$this->getWhereClause();

		$sql.= ' order by '.$this->getOrderByClause($view, 'PinholeTag.title');
		$this->app->db->setLimit($pager->page_size, $pager->current_record);
		$data_objects = SwatDB::query($this->app->db, $sql,
			'PinholeTagDataObjectWrapper');

		if (count($data_objects) > 0) {
			$this->ui->getWidget('results_frame')->visible = true;
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage(Pinhole::_('result'), 
					Pinhole::_('results'));

			foreach ($data_objects as $data_object) {
				$tag = new PinholeTag($data_object);
				$store->add($tag);
			}
		}

		return $store;
	}

	// }}}
}

?>
