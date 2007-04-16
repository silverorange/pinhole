<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminTableStore.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'SwatDB/SwatDB.php';
require_once 'NateGoSearch/NateGoSearchQuery.php';

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

	protected $ui_xml;
	protected $where_clause;
	protected $join_clause;
	protected $order_by_clause;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null)
	{
		parent::__construct($app, $layout);

		$this->ui_xml = dirname(__FILE__).'/index.xml';
	}

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
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$pager = $this->ui->getWidget('pager');
		$pager->process();
	}

	// }}}
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		$processor = new StorePhotoActionsProcessor($this);
		$processor->process($view, $actions);
	}

	// }}}

	// build phase
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		if ($this->where_clause === null) {
			$where = '1 = 1';

			// keywords are included in the where clause if fulltext searching
			// is turned off
			if ($this->getPhotoSearchType() === null) {
				$keyword_where = '';

				$clause = new AdminSearchClause('title');
				$clause->table = 'PinholePhoto';
				$clause->value = $this->ui->getWidget('search_keywords')->value;
				$clause->operator = AdminSearchClause::OP_CONTAINS;
				$keyword_where.= $clause->getClause($this->app->db, '');

				$clause = new AdminSearchClause('bodytext');
				$clause->table = 'PinholePhoto';
				$clause->value = $this->ui->getWidget('search_keywords')->value;
				$clause->operator = AdminSearchClause::OP_CONTAINS;
				$keyword_where.= $clause->getClause($this->app->db, 'or');

				if ($keyword_where != '')
					$where.= sprintf(' and (%s) ',
						$keyword_where);
			}

			$this->where = $where;
		}

		return $this->where;
	}

	// }}}
	// {{{ protected function getTableStore()

	protected function getTableStore($view)
	{
		$this->searchPhotos();

		$sql = sprintf('select count(id) from PinholePhoto %s where %s',
			$this->join_clause,
			$this->getWhereClause());

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = 'select PinholePhoto.id,
					PinholePhoto.title
				from PinholePhoto
				%s
				where %s
				order by %s';

		$sql = sprintf($sql,
			$this->join_clause,
			$this->getWhereClause(),
			$this->getOrderByClause($view, $this->order_by_clause));

		$this->app->db->setLimit($pager->page_size, $pager->current_record);
		$store = SwatDB::query($this->app->db, $sql, 'AdminTableStore');

		$this->ui->getWidget('results_frame')->visible = true;
		$view = $this->ui->getWidget('index_view');

		if ($store->getRowCount() != 0)
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage(Pinhole::_('result'), 
					Pinhole::_('results'));

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
}

?>
