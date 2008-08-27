<?php

require_once 'Admin/pages/AdminDBOrder.php';
require_once 'Admin/AdminUI.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Order page for PinholeMetaData
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeMetaDataOrder extends AdminDBOrder
{
	// {{{ private properties

	private $parent;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->parent = SiteApplication::initVar('parent');
		$form = $this->ui->getWidget('order_form');
		$form->addHiddenField('parent', $this->parent);
	}

	// }}}

	// process phase
	// {{{ protected function saveData()

	protected function saveData()
	{
		parent::saveData();

		if (isset($this->app->memcache))
			$this->app->memcache->flushNs('photos');
	}

	// }}}
	// {{{ protected function saveIndex()

	protected function saveIndex($id, $index)
	{
		SwatDB::updateColumn($this->app->db, 'PinholeMetaData',
			'integer:displayorder', $index, 'integer:id', array($id));

	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()
	protected function buildInternal()
	{
		$frame = $this->ui->getWidget('order_frame');
		$frame->title = Admin::_('Order MetaData');
		parent::buildInternal();
	}

	// }}}
	// {{{ protected function loadData()

	protected function loadData()
	{
		$instance_id = $this->app->getInstanceId();

		$where_clause = sprintf('visible = %s and instance %s %s',
			$this->app->db->quote($this->parent, 'boolean'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$order_widget = $this->ui->getWidget('order');
		$order_widget->addOptionsByArray(SwatDB::getOptionArray($this->app->db,
			'PinholeMetaData', 'title', 'id', 'displayorder, title',
			$where_clause));

		$sql = 'select sum(displayorder) from PinholeMetaData where '.
			$where_clause;
		$sum = SwatDB::queryOne($this->app->db, $sql, 'integer');
		$options_list = $this->ui->getWidget('options');
		$options_list->value = ($sum == 0) ? 'auto' : 'custom';
	}

	// }}}
}

?>
