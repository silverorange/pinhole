<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsView.php';

/**
 * Index page for metadata
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeMetaDataIndex extends AdminIndex
{
	// {{{ protected properties 

	protected $ui_xml =
		'Pinhole/admin/components/MetaData/index.xml';

	// }}}
	
	// init phase
	// {{{ public function init()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		$num = count($view->getSelection());
		$message = null;
		
		switch ($actions->selected->id) {
			case 'show_details':
				SwatDB::updateColumn($this->app->db, 'PinholeMetaData',
					'boolean:show', true, 'id', $view->getSelection());

				$message = new SwatMessage(sprintf(Admin::ngettext(
					"%d detail has been enabled.", 
					"%d details have been enabled.", $num),
					SwatString::numberFormat($num)));

				break;

			case 'dont_show_details':
				SwatDB::updateColumn($this->app->db, 'PinholeMetaData',
					'boolean:show', false, 'id', 
					$view->getSelection());

				$message = new SwatMessage(sprintf(Admin::ngettext(
					"%d detail has been disabled.", 
					"%d details have been disabled.", $num),
					SwatString::numberFormat($num)));

				break;

			case 'show_machine':
				SwatDB::updateColumn($this->app->db, 'PinholeMetaData',
					'boolean:machine_tag', true, 'id', $view->getSelection());

				$message = new SwatMessage(sprintf(Admin::ngettext(
					"%d machine tag has been enabled.", 
					"%d machine tags have been enabled.", $num),
					SwatString::numberFormat($num)));

				break;

			case 'dont_show_machine':
				SwatDB::updateColumn($this->app->db, 'PinholeMetaData',
					'boolean:machine_tag', false, 'id', 
					$view->getSelection());

				$message = new SwatMessage(sprintf(Admin::ngettext(
					"%d machine tag has been disabled.", 
					"%d machine tags have been disabled.", $num),
					SwatString::numberFormat($num)));

				break;

		}
		
		if ($message !== null)
			$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	/**
	 * Gets the metadata for display
	 *
	 * @return SwatTableModel with metadata information.
	 */
	protected function getTableModel(SwatTableView $view)
	{
		$sql = 'select * from PinholeMetaData
			order by %s desc';

		$sql = sprintf($sql,
			$this->getOrderByClause($view, 'show'));
		
		$metadata = SwatDB::query($this->app->db, $sql);
		
		$store = new SwatTableStore();

		foreach ($metadata as $data) {
			$ds = new SwatDetailsView();

			$ds->title       = $data->title;
			$ds->shortname   = $data->shortname;
			$ds->id          = $data->id;
			$ds->show        = $data->show;
			$ds->machine_tag = $data->machine_tag;
			if ($ds->show)
				$ds->group_title = 'Shown';
			else
				$ds->group_title = 'Not Shown';

			$store->add($ds);
		}

		return $store;
	}

	// }}}

}
?>
