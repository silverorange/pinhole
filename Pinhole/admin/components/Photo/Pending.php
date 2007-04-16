<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/AdminTableStore.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';

/**
 * Pending photos page
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoPending extends AdminIndex
{
	// {{{ protected properties

	protected $ui_xml =
		'Pinhole/admin/components/Photo/pending.xml';

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
	 * Processes photo actions
	 *
	 * @param SwatTableView $view the table-view to get selected photographers
	 *                             from.
	 * @param SwatActions $actions the actions list widget.
	 */
	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Photo/Delete');
			$this->app->getPage()->setItems($view->checked_items);
			break;
		case 'publish':
			$items = $view->checked_items;
			foreach ($items as &$item)
				$item = $this->app->db->quote($item, 'integer');

			$sql = sprintf('select * from PinholePhoto where id in (%s) and status = %s',
				implode(',', $items),
				PinholePhoto::STATUS_PENDING);

			$photos = SwatDB::query($this->app->db, $sql, 'PinholePhotoWrapper');

			$count = 0;

			foreach ($photos as $photo) {
				$photo->publish(true);
				$count++;
			}

			if ($count > 0) {
				$message = new SwatMessage(sprintf(Pinhole::ngettext(
					'One photo has been published.',
					'%d photos have been published.', $count),
					SwatString::numberFormat($count)));

				$this->app->messages->add($message);
			}

			break;
		}
	}

	// }}}
	// {{{ protected function getTableStore()

	/**
	 * Gets photo data
	 *
	 * @return AdminTableStore photo information.
	 */
	protected function getTableStore($view)
	{
		$sql = 'select * from PinholePhoto
			where PinholePhoto.status = %s
			order by %s';

		$sql = sprintf($sql,
			$this->app->db->quote(PinholePhoto::STATUS_PENDING, 'integer'),
			$this->getOrderByClause($view, 'id'));
		
		return SwatDB::query($this->app->db, $sql, 'AdminTableStore');
	}

	// }}}
}

?>
