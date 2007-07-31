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
	public $base = 'tag';

	protected $tag_list;
	
	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addStyleSheet(
			'packages/pinhole/styles/pinhole-tag-list-view.css',
			Pinhole::PACKAGE_ID);
	}

	public function display()
	{
		if (!$this->visible)
			return;

		if ($this->tag_list === null)
			return;
		
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'pinhole-tag-list-view';
		$div_tag->id = $this->id;
		$div_tag->open();

		if (count($this->tag_list) > 0) {
			$count = 0;
			foreach ($this->tag_list as $tag) {
				$remove_list = clone $this->tag_list;
				$remove_list->remove($tag);

				if ($count > 0) {
					echo '<span class="pinhole-tag-list-view-operator">+',
						'</span>';
				}

				echo '<span class="pinhole-tag-list-view-tag-wrapper">';

				$only_anchor_tag = new SwatHtmlTag('a');
				$only_anchor_tag->class = 'pinhole-tag-list-view-tag';
				$only_anchor_tag->rel = 'tag';
				$only_anchor_tag->title =
					Pinhole::_('View all photos with this tag.');

				$only_anchor_tag->href = $this->base.'?'.$tag->__toString();
				$only_anchor_tag->setContent($tag->getTitle());
				$only_anchor_tag->display();

				$remove_anchor_tag = new SwatHtmlTag('a');
				$remove_anchor_tag->class = 'pinhole-tag-list-view-remove';
				$remove_anchor_tag->title =
					Pinhole::_('Remove this tag.');

				$remove_anchor_tag->href =
					$this->base.'?'.$remove_list->__toString();

				$remove_anchor_tag->setContent('×');
				$remove_anchor_tag->display();

				echo '</span>';

				unset($remove_list);

				$count++;
			}
		}

		// show a count of photos in the tag list
		$photo_count = $this->tag_list->getPhotoCount();
		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'photo-count';
		$span_tag->setContent(sprintf(Pinhole::ngettext(
			'(%s Photo)', '(%s Photos)',
			$photo_count), SwatString::numberFormat($photo_count)));

		$span_tag->display();

		$div_tag->close();
	}

	public function setTagList(PinholeTagList $tag_list)
	{
		$this->tag_list = $tag_list;
	}
}

?>
