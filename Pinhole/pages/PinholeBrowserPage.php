<?php

require_once 'Swat/SwatUI.php';
require_once 'Swat/exceptions/SwatWidgetNotFoundException.php';
require_once 'Site/pages/SitePage.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/PinholeTagList.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class PinholeBrowserPage extends SitePage
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

	/**
	 * @var string
	 */
	protected $search_ui_xml = 'Pinhole/pages/browser-search.xml';

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
		$this->tag_list = new PinholeTagList($this->app->db,
			$this->app->instance->getInstance(), $tags);
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		$this->ui = new SwatUI();
		$this->ui->mapClassPrefixToPath('Pinhole', 'Pinhole');
		$this->ui->loadFromXML($this->ui_xml);

		$this->initSearchForm();
		$this->initInternal();

		$this->ui->init();
	}

	// }}}
	// {{{ protected function initSearch()

	protected function initSearchForm()
	{
		$this->ui->loadFromXML($this->search_ui_xml,
			$this->ui->getWidget('header_content'));
	}

	// }}}
	// {{{ protected function initTagList()

	protected function initTagList()
	{
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

				$base = $this->app->config->pinhole->path.'tag';

				if ($options->value == 'all') {
					$this->app->relocate($base.'?'.$keyword_tag);
				} else {
					$this->tag_list->add($keyword_tag);
					$this->app->relocate($base.'?'.$this->tag_list->__toString());
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
		if (!$this->ui->hasWidget('tag_list_view'))
			return;

		$tag_list_view = $this->ui->getWidget('tag_list_view');
		$tag_list_view->setTagList($this->tag_list);
		$tag_list_view->base =
			$this->app->config->pinhole->path.$tag_list_view->base;
	}

	// }}}
	// {{{ protected function buildSubTagListView()

	protected function buildSubTagListView()
	{
		if (!$this->ui->hasWidget('sub_tag_list_view'))
			return;

		$range = new SwatDBRange(30, 0);

		$sub_tag_list = $this->getSubTagList($range);
		$sub_tag_count = $this->getSubTagCount();

		$base_path = $this->app->config->pinhole->path;

		$tag_list_view = $this->ui->getWidget('sub_tag_list_view');
		$tag_list_view->setTagList($this->tag_list);
		$tag_list_view->setSubTagList($sub_tag_list);
		$tag_list_view->base = $base_path.'tag';

		$tag_list_view->title = (count($this->tag_list) == 0) ?
			Pinhole::_('Recently Added Tags') :
			Pinhole::_('Popular Related Tags');

		if ($sub_tag_count > count($sub_tag_list)) {
			ob_start();
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'pinhole-sub-tag-more-link';
			$div_tag->open();

			$a_tag = new SwatHtmlTag('a');
			$a_tag->href = $base_path.'tags';
			if (count($this->tag_list) > 0)
				$a_tag->href.= '?'.$this->tag_list->__toString();

			$a_tag->setContent(sprintf(Pinhole::_('View All %s Tags'),
				$sub_tag_count));
			$a_tag->display();

			$div_tag->close();


			$this->ui->getWidget('sub_tag_list_content')->content =
				ob_get_clean();
		}
	}

	// }}}
	// {{{ protected function getSubTagList()

	protected function getSubTagList(SwatDBRange $range = null,
		$order_by_clause = null)
	{
		if (count($this->tag_list) == 0)
			return $this->tag_list->getSubTags($range, $order_by_clause);
		else
			return $this->tag_list->getSubTagsByPopularity(
				$range, $order_by_clause);
	}

	// }}}
	// {{{ protected function getSubTagCount()

	protected function getSubTagCount()
	{
		return $this->tag_list->getSubTagCount();
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

		if (count($this->tag_list) > 0)
			$this->layout->data->rss_link = 'rss?'.$this->tag_list->__toString();
	}

	// }}}
}

?>
