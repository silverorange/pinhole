<?php

require_once 'Swat/SwatYUI.php';
require_once 'Swat/SwatUI.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/PinholeTagIntersection.php';
require_once 'Pinhole/pages/PinholePage.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
abstract class PinholeBrowserPage extends PinholePage
{
	// {{{ protected properties

	/**
	 * @var PinholeTagIntersection
	 */
	protected $tag_intersection;

	protected $search_ui;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout,
		$tags = null)
	{
		parent::__construct($app, $layout);

		$this->tag_intersection = new PinholeTagIntersection($app->db);

		if ($tags !== null)
			foreach (explode('/', $tags) as $tag)
				$this->tag_intersection->addTagByShortname($tag);

		$this->search_ui = new SwatUI();
		$this->search_ui->loadFromXML(
			dirname(__FILE__).'/browser-search.xml');
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		$radio_widget = $this->search_ui->getWidget('search_options');
		$tags = $this->tag_intersection->getIntersectingTags();

		if (count($tags) > 0) {
			$radio_options = array(
				'all' => Pinhole::_('All Photos'),
				'set' => Pinhole::_('This Set'));

			$radio_widget->addOptionsByArray($radio_options);
			$radio_widget->value = 'all';
		} else {
			$radio_widget->visible = false;
		}

		$this->search_ui->getWidget('search_form')->action =
			'tag/'.$this->tag_intersection->getIntersectingTagPath(
				null, array('site.page'));
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		$this->search_ui->process();

		$form = $this->search_ui->getWidget('search_form');

		if ($form->isSubmitted()) {
			$keywords= $this->search_ui->getWidget('keywords')->value;

			$keyword_tag = ($keywords === null) ? '' : sprintf(
					'/search.keywords=%s', urlencode($keywords));

			$options = $this->search_ui->getWidget('search_options')->value;

			if ($options == 'all') {
				$this->app->relocate('tag'.$keyword_tag);
			} else {
				$path = $this->tag_intersection->getIntersectingTagPath(); 
				$this->app->relocate('tag/'.$path.$keyword_tag);
			}
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->startCapture('header_content');
		$this->displayIntersectingTags();
		$this->layout->endCapture();

		$this->layout->startCapture('sidebar_content');
		$this->displayTagList();
		$this->layout->endCapture();

		$this->layout->startCapture('search_content');
		$this->displaySearchForm();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function displayIntersectingTags()

	protected function displayIntersectingTags()
	{
		$tags = $this->tag_intersection->getIntersectingTags();
		if (count($tags) > 0) {
			echo '<div class="intersecting-tag-list">';

			$count = 0;
			
			foreach ($tags as $tag) {
				$title = $tag->getTitle();
				if ($title !== null) {
					if ($count > 0)
						echo ' <span class="plus">+</span> ';

					$tag_link = new SwatHtmlTag('a');
					$tag_link->href = 'tag/'.$tag->getPath();
					$tag_link->rel = 'tag';
					$tag_link->title = 'view all photos with this tag';
					$tag_link->setContent($tag->getTitle());
					$tag_link->display();
					$count++;
				}
			}

			echo '</div>';
		}
	}

	// }}}
	// {{{ protected function displayTagList()

	protected function displayTagList()
	{
		$tags = $this->getTagListTags();
		if (count($tags) > 0) {
			echo '<div id="pinhole_tag_sort"></div>';
			echo '<ul class="tag-list" id="pinhole_tag_list">';

			$root = 'tag';
			$path = $this->tag_intersection->getIntersectingTagPath();
			if (strlen($path) > 0)
				$root.= '/'.$path;

			foreach ($tags as $tag) {
				$li_tag = new SwatHtmlTag('li');
				$li_tag->id = sprintf('%s/%s/%s',
					$tag->shortname,
					$tag->getLastUpdated()->getTime(),
					$tag->getPhotoCount());
				$li_tag->open();

				$anchor_tag = new SwatHtmlTag('a');
				$anchor_tag->setContent($tag->title);
				$anchor_tag->href = $root.'/'.$tag->shortname;
				$anchor_tag->title = sprintf('%d %s',
					$tag->getPhotoCount(),
					Pinhole::ngettext('photo', 'photos', $tag->getPhotoCount()));
				$anchor_tag->rel = 'tag';
				$anchor_tag->display();

				$li_tag->close();

			}

			echo '</ul>';

			Swat::displayInlineJavaScript($this->getInlineJavascript());
		}
	}

	// }}}
	// {{{ protected function displaySearchForm()

	protected function displaySearchForm()
	{
		$this->search_ui->display();
	}

	// }}}
	// {{{ protected function getTagListTags()

	protected function getTagListTags()
	{
		return $this->tag_intersection->getTags();
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		return sprintf("var %s_obj =
			new PinholeSortableTagList('%s', '%s');",
			'tag_list', 'pinhole_tag_list', 'pinhole_tag_sort');
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$yui = new SwatYUI(array('dom', 'animation'));
		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntrySet(
			$this->search_ui->getRoot()->getHtmlHeadEntrySet(),
			Pinhole::PACKAGE_ID);

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/styles/pinhole-browser-page.css',
			Pinhole::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatJavascriptHtmlHeadEntry(
			'packages/pinhole/javascript/pinhole-sortable-tag-list.js',
			Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
