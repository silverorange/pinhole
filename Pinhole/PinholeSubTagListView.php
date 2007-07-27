<?php

require_once 'Swat/SwatControl.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/PinholeTagList.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeSubTagListView extends SwatControl
{
	public $base = 'tag';

	protected $tag_list;

	protected $sub_tag_list;
	
	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addStyleSheet(
			'packages/pinhole/styles/pinhole-sub-tag-list-view.css',
			Pinhole::PACKAGE_ID);
	}

	public function display()
	{
		if (!$this->visible)
			return;

		if ($this->tag_list === null)
			return;

		if ($this->sub_tag_list === null || count($this->sub_tag_list) == 0)
			return;


		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'pinhole-sub-tag-list-view';
		$div_tag->id = $this->id;
		$div_tag->open();

		echo '<ul>';

		foreach ($this->sub_tag_list as $tag) {
			$add_list = clone $this->tag_list;
			$add_list->add($tag);

			echo '<li>';

			$add_anchor_tag = new SwatHtmlTag('a');
			$add_anchor_tag->class = 'pinhole-tag-list-view-tag';
			$add_anchor_tag->rel = 'tag';
//			$add_anchor_tag->title = 
//				Pinhole::_('View all photos with this tag.');

			$add_anchor_tag->href = $this->base.'/'.$add_list->__toString();
			$add_anchor_tag->setContent($tag->getTitle());
			$add_anchor_tag->display();

			echo '</li>';

			unset($add_list);
		}

		echo '</ul>';

		$div_tag->close();
	}

	public function setTagList(PinholeTagList $tag_list)
	{
		$this->tag_list = $tag_list;
	}

	public function setSubTagList(PinholeTagList $tag_list)
	{
		$this->sub_tag_list = $tag_list;
	}
}

?>
