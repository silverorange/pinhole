<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Pinhole/dataobjects/PinholePhotographer.php';

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
		case 'status_action':
			$num = count($view->checked_items);

			$status = $this->ui->getWidget('status_flydown')->value;

			SwatDB::updateColumn($this->app->db, 'PinholePhotographer',
				'integer:status', $status,
				'id', $view->checked_items);

			$message = new SwatMessage(sprintf(Pinhole::ngettext(
				'The status of one photographer has been changed.',
				'The status of %s photographers has been changed.', $num),
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

		$this->ui->getWidget('status_flydown')->addOptionsByArray(
			PinholePhotographer::getStatuses());
	}

	// }}}
	// {{{ protected function getTableModel()

	/**
	 * Gets photographer data
	 *
	 * @return SwatTableModel with photographer information.
	 */
	protected function getTableModel(SwatView $view)
	{
		$sql = 'select * from PinholePhotographer
			where instance %s %s
			order by %s';

		$instance_id = $this->app->instance->getId();

		$sql = sprintf($sql,
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->getOrderByClause($view, 'id'));

		$rs = SwatDB::query($this->app->db, $sql);

		foreach ($rs as $row)
			$row->status_title =
				PinholePhotographer::getStatusTitle($row->status);

		return $rs;
	}

	// }}}
}

?>
