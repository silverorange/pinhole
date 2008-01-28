<?php

require_once 'Admin/pages/AdminIndex.php';

/**
 * Index page for layouts
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeLayoutIndex extends AdminIndex
{
	// {{{ protected properties

	protected $ui_xml =
		'Pinhole/admin/components/PinholeLayout/index.xml';

	// }}}

	// init phase
	// {{{ public function init()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	/**
	 * Gets the layout information to display
	 *
	 * @return SwatTableModel with layout information.
	 */
	protected function getTableModel(SwatView $view)
	{
		$sql = 'select * from PinholeLayouts
			order by %s';

		$sql = sprintf($sql,
			$this->getOrderByClause($view, 'title'));

		$rs = SwatDB::query($this->app->db, $sql);

		return $rs;
	}

	// }}}

}
?>
