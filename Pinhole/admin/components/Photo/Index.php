<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'NateGoSearch/NateGoSearchQuery.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'include/PinholePhotoTagEntry.php';
require_once 'include/PinholeAdminPhotoCellRenderer.php';
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

		$sql = sprintf('select * from PinholeTag
			where status = %s order by title',
			$this->app->db->quote(PinholeTag::STATUS_ENABLED, 'integer'));

		$tags = SwatDB::query($this->app->db, $sql, 'PinholeTagWrapper');

		$this->ui->getWidget('tags')->tags = $tags;

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
			$where = sprintf('PinholePhoto.status != %s',
				$this->app->db->quote(PinholePhoto::STATUS_PENDING,
					'integer'));

			// keywords are included in the where clause if fulltext searching
			// is turned off
			if ($this->getPhotoSearchType() === null) {
				$keyword_where = '';

				$clause = new AdminSearchClause('title');
				$clause->table = 'PinholePhoto';
				$clause->value = $this->ui->getWidget('search_keywords')->value;
				$clause->operator = AdminSearchClause::OP_CONTAINS;
				$keyword_where.= $clause->getClause($this->app->db, '');

				$clause = new AdminSearchClause('description');
				$clause->table = 'PinholePhoto';
				$clause->value = $this->ui->getWidget('search_keywords')->value;
				$clause->operator = AdminSearchClause::OP_CONTAINS;
				$keyword_where.= $clause->getClause($this->app->db, 'or');

				if ($keyword_where != '')
					$where.= sprintf(' and (%s) ',
						$keyword_where);
			}

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

	protected function getTableModel(SwatTableView $view)
	{
		$this->searchPhotos();

		$sql = sprintf('select count(id) from PinholePhoto %s where %s',
			$this->join_clause,
			$this->getWhereClause());

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$photos = PinholePhotoWrapper::loadSetFromDBWithDimension(
			$this->app->db, 'thumb', $this->getWhereClause(),
			$this->join_clause, null,
			$pager->page_size, $pager->current_record);

		$this->ui->getWidget('results_frame')->visible = true;

		$store = new SwatTableStore();

		if (count($photos) != 0) {
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage(Pinhole::_('result'), 
					Pinhole::_('results'));

			foreach ($photos as $photo) {
				$ds = new SwatDetailsStore($photo);
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
		if (strlen(trim($keywords)) > 0 &&
			$this->getPhotoSearchType() !== null) {

			$query = new NateGoSearchQuery($this->app->db);
			$query->addDocumentType($this->getPhotoSearchType());
			$query->addBlockedWords(
				NateGoSearchQuery::getDefaultBlockedWords());

			$result = $query->query($keywords);

			$this->join_clause = sprintf(
				'inner join %1$s on
					%1$s.document_id = PinholePhoto.id and
					%1$s.unique_id = %2$s and %1$s.document_type = %3$s',
				$result->getResultTable(),
				$this->app->db->quote($result->getUniqueId(), 'text'),
				$this->app->db->quote($this->getPhotoSearchType(),
					'integer'));

			$this->order_by_clause =
				sprintf('%1$s.displayorder1, %1$s.displayorder2, PinholePhoto.title',
					$result->getResultTable());
		} else {
			$this->join_clause = '';
			$this->order_by_clause = 'PinholePhoto.title';
		}
	}

	// }}}
	// {{{ protected function getPhotoSearchType()

	/**
	 * Gets the search type for photos for this web-application
	 *
	 * @return integer the search type for photos for this web-application or
	 *                  null if fulltext searching is not implemented for the
	 *                  current application.
	 */
	protected function getPhotoSearchType()
	{
		return null;
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
