<?php

require_once 'Swat/SwatUI.php';
require_once 'Swat/exceptions/SwatWidgetNotFoundException.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/PinholeTagList.php';
require_once 'Pinhole/pages/PinholePage.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class PinholeBrowserPage extends PinholePage
{
	// {{{ protected properties

	/**
	 * @var SwatUI
	 */
	protected $ui;

	/**
	 * @var string
	 */
	protected $ui_xml;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout)
	{
		parent::__construct($app, $layout);
		$tags = SiteApplication::initVar('tags');
		$this->createTagList($tags);
	}

	// }}}
	// {{{ protected function createTagList()

	protected function createTagList($tags)
	{
		$this->tag_list = new PinholeTagList($this->app->db, $tags);
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		$this->ui = new SwatUI();
		$this->ui->mapClassPrefixToPath('Pinhole', 'Pinhole');
		$this->ui->loadFromXML($this->ui_xml);

		$this->initInternal();

		$this->ui->init();
	}

	// }}}
	// {{{ protected function initTagList()

	protected function initTagList()
	{
		$this->tag_list->setInstance($this->app->instance->getInstance());

		$this->tag_list->setPhotoWhereClause(sprintf(
			'PinholePhoto.status = %s',
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer')));

		if (count($this->tag_list) == 0) {
			// if we're at the root, show newest photos first
			$this->tag_list->setPhotoOrderByClause(
				'PinholePhoto.publish_date desc, id desc');
		} else { 
			// if we have tags selected, show oldest photos first
			$this->tag_list->setPhotoOrderByClause(
				'coalesce(PinholePhoto.photo_date, '.
					'PinholePhoto.publish_date) asc, id asc');
		}
	}

	// }}}
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->initTagList();
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		$this->ui->process();
		$this->processInternal();
	}

	// }}}
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		$this->processSearchForm();
	}

	// }}}
	// {{{ protected function processSearchForm()

	protected function processSearchForm()
	{
		try {
			$form = $this->ui->getWidget('search_form');
			if ($form->isSubmitted()) {
				$keywords= $this->ui->getWidget('keywords')->value;

				$keyword_tag = ($keywords === null) ? '' : sprintf(
					'search.keywords=%s', urlencode($keywords));

				$options = $this->ui->getWidget('search_options');
				if ($options->value == 'all') {
					$this->app->relocate('tag?'.$keyword_tag);
				} else {
					$this->tag_list->add($keyword_tag);
					$this->app->relocate('tag?'.$this->tag_list->__toString());
				}
			}
		} catch (SwatWidgetNotFoundException $e) {
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->buildInternal();

		$this->layout->startCapture('header_content');
		$this->displayHeaderContent();
		$this->layout->endCapture();

		$this->layout->startCapture('sidebar_content');
		$this->displaySidebarContent();
		$this->layout->endCapture();

		$this->layout->startCapture('content');
		$this->displayContent();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		$this->buildSearchForm();
		$this->buildTagListView();
		$this->buildSubTagListView();
	}

	// }}}
	// {{{ protected function buildSearchForm()

	protected function buildSearchForm()
	{
		try {
			$radio_widget = $this->ui->getWidget('search_options');

			if (count($this->tag_list) > 0) {
				$radio_options = array(
					'all' => Pinhole::_('All Photos'),
					'set' => Pinhole::_('This Set'));

				$radio_widget->addOptionsByArray($radio_options);
				$radio_widget->value = 'all';
			} else {
				$radio_widget->visible = false;
			}

			$this->ui->getWidget('search_form')->action = $this->app->getUri();
		} catch (SwatWidgetNotFoundException $e) {
		}
	}

	// }}}
	// {{{ protected function buildTagListView()

	protected function buildTagListView()
	{
		try {
			$tag_list_view = $this->ui->getWidget('tag_list_view');
			$tag_list_view->setTagList($this->tag_list);
		} catch (SwatWidgetNotFoundException $e) {
		}
	}

	// }}}
	// {{{ protected function buildSubTagListView()

	protected function buildSubTagListView()
	{
		try {
			$tag_list_view = $this->ui->getWidget('sub_tag_list_view');
			$tag_list_view->setTagList($this->tag_list);
			$tag_list_view->setSubTagList($this->getSubTagList());
		} catch (SwatWidgetNotFoundException $e) {
		}
	}

	// }}}
	// {{{ protected function getSubTagList()

	protected function getSubTagList()
	{
		return $this->tag_list->getSubTags();
	}

	// }}}
	// {{{ protected function displayHeaderContent()

	protected function displayHeaderContent()
	{
		try {
			$header = $this->ui->getWidget('header_content');
			$header->display();
		} catch (SwatWidgetNotFoundException $e) {
		}
	}

	// }}}
	// {{{ protected function displaySidebarContent()

	protected function displaySidebarContent()
	{
		try {
			$sidebar = $this->ui->getWidget('sidebar_content');
			$sidebar->display();
		} catch (SwatWidgetNotFoundException $e) {
		}
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		try {
			$content = $this->ui->getWidget('content');
			$content->display();
		} catch (SwatWidgetNotFoundException $e) {
		}
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/styles/pinhole-browser-page.css',
			Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
