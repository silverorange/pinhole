<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/AdminTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'include/PinholeAdminPhotoCellRenderer.php';

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
	protected function processActions(SwatView $view, SwatActions $actions)
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

			$sql = sprintf('select * from PinholePhoto
				where id in (%s) and %s',
				implode(',', $items),
				$this->getWhereClause());

			$photos = SwatDB::query($this->app->db, $sql,
				'PinholePhotoWrapper');

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

	protected function getTableStore($view)
	{
		$sql = sprintf('select count(id) from PinholePhoto where %s',
			$this->getWhereClause());

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$photos = PinholePhotoWrapper::loadSetFromDBWithDimension(
			$this->app->db, 'thumb', $this->getWhereClause(),
			'', 'PinholePhoto.upload_date, PinholePhoto.id',
			$pager->page_size, $pager->current_record);

		$store = new SwatTableStore();

		if (count($photos) != 0) {
			foreach ($photos as $photo) {
				$ds = new SwatDetailsStore($photo);
				$ds->photo = $photo;
				$store->addRow($ds);
			}
		}

		return $store;
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		return sprintf('PinholePhoto.status = %s',
			$this->app->db->quote(PinholePhoto::STATUS_PENDING,
			'integer'));
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/admin/styles/pinhole-photo-tile.css',
			Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
