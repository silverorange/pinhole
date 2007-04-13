<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/AdminTableStore.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Index page for photographers
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotographerIndex extends AdminIndex
{
	// {{{ protected properties

	protected $ui_xml =
		'Pinhole/admin/components/Photographer/index.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	/**
	 * Processes photographer actions
	 *
	 * @param SwatTableView $view the table-view to get selected photographers
	 *                             from.
	 * @param SwatActions $actions the actions list widget.
	 */
	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Photographer/Delete');
			$this->app->getPage()->setItems($view->checked_items);
			break;
		}
	}

	// }}}
	// {{{ protected function getTableStore()

	/**
	 * Gets photographer data
	 *
	 * @return AdminTableStore photographer information.
	 */
	protected function getTableStore($view)
	{
		$sql = 'select * from PinholePhotographer
			order by %s';

		$sql = sprintf($sql,
			$this->getOrderByClause($view, 'id'));
		
		return SwatDB::query($this->app->db, $sql, 'AdminTableStore');
	}

	// }}}
}

?>
