<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatTableStore.php';
require_once 'NateGoSearch/NateGoSearchQuery.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Pinhole/dataobjects/PinholeTagDataObjectWrapper.php';
require_once 'include/PinholePhotoTagEntry.php';
require_once 'include/PinholePhotoActionsProcessor.php';

/**
 * Index page for photographs
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoIndex extends AdminSearch
{
	// {{{ protected properties

	protected $ui_xml = 'Pinhole/admin/components/Photo/index.xml';
	protected $where_clause;
	protected $join_clause;
	protected $order_by_clause;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		$instance_id = $this->app->instance->getId();

		// setup tag entry control
		$tag_list = new PinholeTagList($this->app->db,
			$this->app->instance->getInstance());

		$sql = sprintf('select * from PinholeTag
			where instance %s %s order by title',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$tags = SwatDB::query($this->app->db, $sql,
			'PinholeTagDataObjectWrapper');

		foreach ($tags as $data_object)
			$tag_list->add(new PinholeTag($data_object));

		$this->ui->getWidget('tags')->setTagList($tag_list);
		$this->ui->getWidget('tags')->setDatabase($this->app->db);

		// setup status list
		$status_flydown = $this->ui->getWidget('status_flydown');
		$status_flydown->addOptionsByArray(PinholePhoto::getStatuses());
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
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		if ($this->where_clause === null) {
			$instance_id = $this->app->instance->getId();

			$where = sprintf('PinholePhoto.status != %s
				and ImageSet.instance %s %s',
				$this->app->db->quote(PinholePhoto::STATUS_PENDING, 'integer'),
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'));

			$clause = new AdminSearchClause('date:photo_date');
			$clause->table = 'PinholePhoto';
			$clause->value = $this->ui->getWidget('search_start_date')->value;
			$clause->operator = AdminSearchClause::OP_GTE;
			$where.= $clause->getClause($this->app->db, 'and');

			$clause = new AdminSearchClause('date:photo_date');
			$clause->table = 'PinholePhoto';
			$clause->value = $this->ui->getWidget('search_end_date')->value;
			$clause->operator = AdminSearchClause::OP_LT;
			$where.= $clause->getClause($this->app->db, 'and');

			$this->where = $where;
		}

		return $this->where;
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$this->searchPhotos();

		$sql = sprintf('select count(PinholePhoto.id)
			from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			%s where %s',
			$this->join_clause,
			$this->getWhereClause());

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = sprintf('select PinholePhoto.* from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			%s where %s order by %s',
			$this->join_clause,
			$this->getWhereClause(),
			$this->order_by_clause);

		$this->app->db->setLimit($pager->page_size, $pager->current_record);

		$wrapper_class = SwatDBClassMap::get('PinholePhotoWrapper');
		$photos = SwatDB::query($this->app->db, $sql, $wrapper_class);

		$this->ui->getWidget('results_frame')->visible = true;

		$store = new SwatTableStore();

		if (count($photos) != 0) {
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage(Pinhole::_('result'),
					Pinhole::_('results'));

			foreach ($photos as $photo) {
				$ds = new SwatDetailsStore();
				$ds->photo = $photo;
				$store->add($ds);
			}
		}

		return $store;
	}

	// }}}
	// {{{ protected function searchPhotos()

	protected function searchPhotos()
	{
		$keywords = $this->ui->getWidget('search_keywords')->value;

		$this->join_clause = '';
		$this->order_by_clause = 'PinholePhoto.publish_date desc,
			PinholePhoto.photo_date desc, PinholePhoto.id';

		if (strlen(trim($keywords)) > 0) {
			$query = new NateGoSearchQuery($this->app->db);
			$query->addDocumentType('photo');
			$query->addBlockedWords(
				NateGoSearchQuery::getDefaultBlockedWords());

			$result = $query->query($keywords);
			$type = NateGoSearch::getDocumentType($this->app->db, 'photo');

			$this->join_clause = sprintf(
				'inner join %1$s on
					%1$s.document_id = PinholePhoto.id and
					%1$s.unique_id = %2$s and %1$s.document_type = %3$s',
				$result->getResultTable(),
				$this->app->db->quote($result->getUniqueId(), 'text'),
				$this->app->db->quote($type, 'integer'));

			$this->order_by_clause =
				sprintf('%1$s.displayorder1, %1$s.displayorder2, %2$s',
					$result->getResultTable(),
					$this->order_by_clause);
		}
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
