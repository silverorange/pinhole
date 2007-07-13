<?php

require_once 'Pinhole/dataobjects/PinholeTag.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatNavBar.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';

/**
 * Details page for Tags
 *
 * @package   Pinhole
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeTagDetails extends AdminIndex
{
	// {{{ protected properties

	protected $ui_xml = 'Pinhole/admin/components/Tag/details.xml';
	protected $id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);
		$this->id = SiteApplication::initVar('id');
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->buildTag();
		$this->buildChildren();
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatTableView $view)
	{
		switch ($view->id) {
			case 'children_view':
				return $this->getChildrenTableModel($view);
		}
	}

	// }}}

	// build phase - tag details
	// {{{ private function buildTag()

	private function buildTag()
	{
		$tag = $this->loadTag();
		$ds = new SwatDetailsStore($tag);
		$details_view = $this->ui->getWidget('details_view');
		$details_view->data = $ds;

		$details_frame = $this->ui->getWidget('details_frame');
		$details_frame->title = Pinhole::_('Tag');
		$details_frame->subtitle = $tag->title;
		$this->title = $tag->title;

		$toolbar = $this->ui->getWidget('details_toolbar');
		$toolbar->setToolLinkValues(array($this->id));
		$this->ui->getWidget('view_in_gallery')->link =
			'photos/'.$tag->path;
		$this->buildNavBar($tag);
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar($tag)
	{
		$this->navbar->popEntry();
		$this->navbar->addEntry(new SwatNavBarEntry(
			Pinhole::_('Tags'), 'Tag'));

		$tag_navbar_rs = SwatDB::executeStoredProc($this->app->db,
			'getPinholeTagNavbar', array($this->id));

		foreach ($tag_navbar_rs as $entry)
			if ($entry->id !== $tag->id)
				$this->navbar->addEntry(new SwatNavBarEntry(
					$entry->title,
					'Tag/Details?id='.$entry->id));

		$this->navbar->addEntry(new SwatNavBarEntry($tag->title));
	}

	// }}}
	// {{{ private function loadTag()

	private function loadTag()
	{
		$tag = new PinholeTag();
		$tag->setDatabase($this->app->db);

		if (!$tag->load($this->id))
			throw new AdminNotFoundException(sprintf(
				Pinhole::_('A tag with an id of ‘%d’ does not exist.'),
				$this->id));

		return $tag;
	}

	// }}}

	// build phase - sub-tags
	// {{{ private function buildChildren()

	private function buildChildren()
	{
		$toolbar = $this->ui->getWidget('children_toolbar');
		$toolbar->setToolLinkValues(array($this->id));
	}

	// }}}
	// {{{ private function getChildrenTableModel()

	private function getChildrenTableModel(SwatTableView $view)
	{
		$sql = 'select id, title, shortname
			from PinholeTag
			where parent = %s
				and name_space is null
			order by title';

		$sql = sprintf($sql,
			$this->app->db->quote($this->id, 'integer'));

		$rs = SwatDB::query($this->app->db, $sql);

		return $rs;
	}

	// }}}
}

?>
