<?php

/**
 * Search page for geo-tagging
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeGeoTagSearch extends AdminSearch
{
	// {{{ protected properties

	protected $ui_xml = __DIR__.'/search.xml';
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

		$instance_id = $this->app->getInstanceId();

		// setup tag entry control
		$this->ui->getWidget('search_tags')->setApplication($this->app);
		$this->ui->getWidget('search_tags')->setAllTags();

		// setup status list
		$search_status_flydown = $this->ui->getWidget('search_status');
		$search_status_flydown->addOptionsByArray(array(
			'published' => PinholePhoto::getStatusTitle(
				PinholePhoto::STATUS_PUBLISHED),

			'hidden' => PinholePhoto::getStatusTitle(
				PinholePhoto::STATUS_UNPUBLISHED),

			'private' => Pinhole::_('Private'),
			'public' => Pinhole::_('Public'),

			'for_sale' => Pinhole::_('For-Sale'),
			'not_for_sale' => Pinhole::_('Not For-Sale'),
		));
	}

	// }}}
	// {{{ protected function createLayout()

	protected function createLayout()
	{
		return new SiteLayout($this->app,
			'Pinhole/admin/components/GeoTag/search-layout.php');
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$pager = $this->ui->getWidget('pager');
		$pager->process();

		$this->ui->getWidget('search_disclosure')->open = (!$this->hasState());
	}

	// }}}
	// {{{ protected function hasSearch()

	protected function hasSearch()
	{
		$keywords   = trim($this->ui->getWidget('search_keywords')->value);
		$start_date = $this->ui->getWidget('search_start_date')->value;
		$end_date   = $this->ui->getWidget('search_end_date')->value;
		$tags       = $this->ui->getWidget('search_tags')->getSelectedTagArray();
		$status     = $this->ui->getWidget('search_status')->value;
		$non_tagged = $this->ui->getWidget('search_non_geo_tagged')->value;

		return ($keywords != ''
			|| $start_date != null
			|| $end_date != null
			|| count($tags)
			|| $non_tagged
			|| $status != null);
	}

	// }}}

	// build phase
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		if ($this->where_clause === null) {
			$instance_id = $this->app->getInstanceId();

			$where = sprintf('PinholePhoto.status in (%s, %s)
				and ImageSet.instance %s %s',
				$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer'),
				$this->app->db->quote(PinholePhoto::STATUS_UNPUBLISHED, 'integer'),
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

			if ($this->ui->getWidget('search_non_geo_tagged')->value) {
				$where.= sprintf(' and PinholePhoto.gps_latitude is null ');
			}

			$status = $this->ui->getWidget('search_status')->value;
			if ($status !== null) {
				switch ($status) {
				case 'published' :
					$where.= sprintf(' and PinholePhoto.status = %s',
						$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED,
							'integer'));

					break;
				case 'hidden' :
					$where.= sprintf(' and PinholePhoto.status = %s',
						$this->app->db->quote(PinholePhoto::STATUS_UNPUBLISHED,
							'integer'));

					break;

				case 'private' :
				case 'public' :
					$where.= sprintf(' and PinholePhoto.private = %s',
						$this->app->db->quote($status == 'private',
							'boolean'));

					break;

				case 'for_sale' :
				case 'not_for_sale' :
					$where.= sprintf(' and PinholePhoto.for_sale = %s',
						$this->app->db->quote($status == 'for_sale',
							'boolean'));

					break;
				}
			}

			$tags = $this->ui->getWidget('search_tags')->getSelectedTagArray();
			foreach ($tags as $name => $title)
				$where.= sprintf(' and PinholePhoto.id in (select photo from
					PinholePhotoTagBinding inner join PinholeTag on
					PinholeTag.id = PinholePhotoTagBinding.tag
					where PinholeTag.name = %s)',
					$this->app->db->quote($name, 'text'));

			$this->where = $where;
		}

		return $this->where;
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		if (!$this->hasState()) {
			$this->ui->getWidget('results_frame')->visible = false;
			return;
		}

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
			foreach ($photos as $photo) {
				$ds = new SwatDetailsStore();
				$ds->photo = $photo;
				$ds->class_name = $this->getTileClasses($photo);
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
			PinholePhoto.photo_date asc, PinholePhoto.id';

		if (trim($keywords) != '') {
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
	// {{{ protected function getTileClasses()

	protected function getTileClasses(PinholePhoto $photo)
	{
		$classes = array();

		if (!$photo->isPublished())
			$classes[] = 'insensitive';

		if ($photo->private)
			$classes[] = 'private';

		if ($photo->gps_latitude && $photo->gps_longitude)
			$classes[] = 'geo-tagged';

		return implode(' ', $classes);
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

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/admin/styles/pinhole-geo-tag-search.css',
			Pinhole::PACKAGE_ID));

		$yui = new SwatYUI(array('fonts'));
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/admin/styles/admin-layout.css', Admin::PACKAGE_ID));
	}

	// }}}
}

?>
