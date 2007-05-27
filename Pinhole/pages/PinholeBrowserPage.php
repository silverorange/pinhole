<?php

require_once 'Swat/SwatYUI.php';
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
			echo '<h3 class="tag-list-title">Related Tags:</h3>';
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

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/styles/pinhole-browser-page.css',
			Pinhole::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatJavascriptHtmlHeadEntry(
			'packages/pinhole/javascript/pinhole-sortable-tag-list.js', Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
