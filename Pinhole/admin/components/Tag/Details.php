<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatNavBar.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Pinhole/PinholeTagList.php';
require_once 'Pinhole/tags/PinholeTag.php';
require_once 'Pinhole/admin/PinholePhotoTagEntry.php';
require_once 'Pinhole/admin/components/Photo/include/PinholePhotoActionsProcessor.php';

/**
 * Details page for tags
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeTagDetails extends AdminIndex
{
	// {{{ protected properties

	protected $ui_xml = 'Pinhole/admin/components/Tag/details.xml';
	protected $id;

	// }}}
	// {{{ private properties

	/**
	 * @var PinholeTag
	 */
	private $tag;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->id = SiteApplication::initVar('id');
		$this->initTag();

		$this->ui->loadFromXML($this->ui_xml);
		$instance_id = $this->app->getInstanceId();

		$this->ui->getWidget('passphrase_field')->visible =
			($this->app->config->pinhole->passphrase === null);

		// setup tag entry control
		$this->ui->getWidget('tags')->setApplication($this->app);
		$this->ui->getWidget('tags')->setAllTags();

		// setup status list
		$status_flydown = $this->ui->getWidget('status_flydown');
		$status_flydown->addOptionsByArray(PinholePhoto::getStatuses());
	}

	// }}}
	// {{{ private function initTag()

	private function initTag()
	{
		$class_name = SwatDBClassMap::get('PinholeTag');
		$this->tag = new $class_name();
		$this->tag->setDatabase($this->app->db);
		$this->tag->setInstance($this->app->getInstance());

		if (!$this->tag->load($this->id))
			throw new AdminNotFoundException(
				sprintf(Pinhole::_('Tag with id “%s” not found.'), $this->id));
	}

	// }}}

	// process phase
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
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$ds = new SwatDetailsStore($this->tag);
		$ds->photo_count = $this->tag->getPhotoCount();

		$details_view = $this->ui->getWidget('details_view');
		$details_view->data = $ds;

		$details_frame = $this->ui->getWidget('details_frame');
		$details_frame->title = Pinhole::_('Tag');
		$details_frame->subtitle = $this->tag->title;

		$this->ui->getWidget('for_sale')->visible =
		$this->ui->getWidget('not_for_sale')->visible =
		$this->ui->getWidget('for_sale_divider')->visible =
			($this->app->config->clustershot->username !== null);

		$this->buildToolbar();
	}

	// }}}
	// {{{ protected function buildToolbar()

	protected function buildToolbar()
	{
		$this->ui->getWidget('edit_tool_link')->link =
			$this->getComponentName().'/Edit?id=%s';

		$this->ui->getWidget('delete_tool_link')->link =
			$this->getComponentName().'/Delete?id=%s';

		$toolbar = $this->ui->getWidget('details_toolbar');
		$toolbar->setToolLinkValues(array($this->id));

		$toolbar = $this->ui->getWidget('photo_toolbar');
		$toolbar->setToolLinkValues(array($this->id));

		$this->ui->getWidget('order_tool_link')->link =
			$this->getComponentName().'/PhotoOrder?id=%s';

		/*
		$this->ui->getWidget('view_in_gallery')->link =
			'photos/'.$this->tag->name;
		*/
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->popEntry();
		$this->navbar->addEntry(new SwatNavBarEntry($this->getComponentTitle(),
			$this->getComponentName()));

		$this->navbar->addEntry(new SwatNavBarEntry($this->tag->title));
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$tag_list = new PinholeTagList($this->app->db,
			$this->app->getInstance(), $this->tag->name, true);

		$photos = $tag_list->getPhotos();
		$store = new SwatTableStore();

		foreach ($photos as $photo) {
			$ds = new SwatDetailsStore();
			$ds->photo = $photo;
			$ds->class_name = $this->getTileClasses($photo);
			$store->add($ds);
		}

		return $store;
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
	}

	// }}}
}

?>
