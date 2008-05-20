<?php

require_once 'Swat/SwatControl.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/PinholeTagList.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeTagListView extends SwatControl
{
	// {{{ public properties

	public $home_title;
	public $base = 'tag';
	public $rss_dimension_shortname = 'large';

	// }}}
	// {{{ protected properties

	protected $tag_list;

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addStyleSheet(
			'packages/pinhole/styles/pinhole-tag-list-view.css',
			Pinhole::PACKAGE_ID);
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		if ($this->tag_list === null)
			return;

		parent::display();

		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'pinhole-tag-list-view';
		$div_tag->id = $this->id;
		$div_tag->open();

		$this->displayHomeLink();

		if (count($this->tag_list) > 0)
			$this->displayTagList($this->tag_list);

		$this->displayCount();
		echo ' ';
		$this->displayRssLink();

		$div_tag->close();
	}

	// }}}
	// {{{ public function setTagList()

	public function setTagList(PinholeTagList $tag_list)
	{
		$this->tag_list = $tag_list;
	}

	// }}}
	// {{{ protected function displayTagList()

	protected function displayTagList(PinholeTagList $tag_list)
	{
		$count = 0;
		foreach ($tag_list as $tag) {
			$tag_anchor_tag = new SwatHtmlTag('a');
			$tag_anchor_tag->class = 'tag';
			$tag_anchor_tag->rel = 'tag';
			$tag_anchor_tag->href = $this->base.'?'.$tag->__toString();
			$tag_anchor_tag->setContent($tag->getTitle());
			$tag_anchor_tag->title =
				Pinhole::_('View all photos with this tag');

			$remove_list = clone $this->tag_list;
			$remove_list->remove($tag);
			$remove_anchor_tag = new SwatHtmlTag('a');
			$remove_anchor_tag->class = 'remove';
			$remove_anchor_tag->title = Pinhole::_('Remove this tag');
			$remove_anchor_tag->setContent('×');
			$remove_anchor_tag->href =
				$this->base.'?'.$remove_list->__toString();

			unset($remove_list);

			if ($count > 0)
				echo '<span class="operator">+</span>';

			echo '<span class="tag-wrapper">';
			$tag_anchor_tag->display();
			$remove_anchor_tag->display();
			echo '</span>';

			$count++;
		}
	}

	// }}}
	// {{{ protected function displayCount()

	protected function displayCount()
	{
		// show a count of photos in the tag list
		$photo_count = $this->tag_list->getPhotoCount();
		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'photo-count';
		$span_tag->setContent(
			sprintf(Pinhole::ngettext('(%s Photo)', '(%s Photos)',
			$photo_count), SwatString::numberFormat($photo_count)));

		$span_tag->display();
	}

	// }}}
	// {{{ protected function displayRssLink()

	protected function displayRssLink()
	{
		$rss_link_tag = new SwatHtmlTag('a');
		$rss_link_tag->class = 'rss';
		$rss_link_tag->title = Pinhole::_('Feed for this set of photos');
		$rss_link_tag->href = str_replace('tag',
			'rss/'.$this->rss_dimension_shortname, $this->base);

		if (count($this->tag_list) > 0)
			$rss_link_tag->href.= '?'.$this->tag_list->__toString();

		$rss_link_tag->setContent(Pinhole::_('Feed'));

		$rss_link_tag->display();
	}

	// }}}
	// {{{ protected function displayHomeLink()

	protected function displayHomeLink()
	{
		if ($this->home_title !== null) {
			$tag_anchor_tag = new SwatHtmlTag('a');
			$tag_anchor_tag->class = 'tag';
			$tag_anchor_tag->href = $this->base;
			$tag_anchor_tag->setContent($this->home_title);

			echo '<span class="tag-wrapper">';
			$tag_anchor_tag->display();

			if (count($this->tag_list) > 0)
				echo ' » ';

			echo '</span>';
		}
	}

	// }}}
}

?>
