<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatNavBar.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Pinhole/tags/PinholeTag.php';

/**
 * Details page for tags
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeTagDetails extends AdminPage
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

		$this->ui->loadFromXML($this->ui_xml);
		$this->id = SiteApplication::initVar('id');

		$this->initTag();
	}

	// }}}
	// {{{ private function initTag()

	private function initTag()
	{
		$class_name = SwatDBClassMap::get('PinholeTag');
		$this->tag = new $class_name();
		$this->tag->setDatabase($this->app->db);
		$this->tag->setInstance($this->app->instance->getInstance());

		if (!$this->tag->load($this->id))
			throw new AdminNotFoundException(
				sprintf(Pinhole::_('Tag with id “%s” not found.'),
					$this->id));
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

		$toolbar = $this->ui->getWidget('details_toolbar');
		$toolbar->setToolLinkValues(array($this->id));

		/*
		$this->ui->getWidget('view_in_gallery')->link =
			'photos/'.$this->tag->name;
		*/

		$this->buildNavBar();
	}

	// }}}
	// {{{ private function buildNavBar()

	private function buildNavBar()
	{
		$this->navbar->popEntry();
		$this->navbar->addEntry(
			new SwatNavBarEntry(Pinhole::_('Tags'), $this->getComponentName()));

		$this->navbar->addEntry(new SwatNavBarEntry($this->tag->title));
	}

	// }}}
}

?>
