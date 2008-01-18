<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatTableStore.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'include/PinholeAdminPhotoCellRenderer.php';
require_once 'include/PinholePhotoActionsProcessor.php';
require_once 'include/PinholePhotoTagEntry.php';

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

		// setup tag entry control
		$tag_list = new PinholeTagList($this->app->db,
			$this->app->instance->getInstance());

		$sql = sprintf('select * from PinholeTag
			where instance %s %s order by title',
			SwatDB::equalityOperator($this->app->instance->getId()),
			$this->app->db->quote($this->app->instance->getId(), 'integer'));

		$tags = SwatDB::query($this->app->db, $sql,
			'PinholeTagDataObjectWrapper');
		
		foreach ($tags as $data_object) {
			$tag = new PinholeTag($data_object);
			$tag_list->add($tag);
		}

		$this->ui->getWidget('tags')->setTagList($tag_list);
		$this->ui->getWidget('tags')->setDatabase($this->app->db);
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$pager = $this->ui->getWidget('pager');
		$pager->process();
	}

	// }}}
	// {{{ protected function processActions()

	/**
	 * Processes photo actions
	 *
	 * @param SwatView $view the table-view to get selected photos
	 *                 from.
	 * @param SwatActions $actions the actions list widget.
	 */
	protected function processActions(SwatView $view, SwatActions $actions)
	{
		$processor = new PinholePhotoActionsProcessor($this);
		$processor->process($view, $actions, $this->ui);
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
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
				$store->add($ds);
			}
		}

		return $store;
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		return sprintf('PinholePhoto.status = %s
			and PinholePhoto.instance %s %s',
			$this->app->db->quote(PinholePhoto::STATUS_PENDING, 'integer'),
			SwatDB::equalityOperator($this->app->instance->getId()),
			$this->app->db->quote($this->app->instance->getId(), 'integer')
			);
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
