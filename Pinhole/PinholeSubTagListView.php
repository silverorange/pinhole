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
	// {{{ public properties

	public $title;
	public $base = 'tag';

	// }}}
	// {{{ protected properties

	protected $tag_list;

	protected $sub_tag_list;

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		/*
		$this->addStyleSheet(
			'packages/pinhole/styles/pinhole-sub-tag-list-view.css',
			Pinhole::PACKAGE_ID);
		*/
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		if ($this->tag_list === null)
			return;

		if ($this->sub_tag_list === null || count($this->sub_tag_list) == 0)
			return;

		parent::display();

		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'pinhole-sub-tag-list-view';
		$div_tag->id = $this->id;
		$div_tag->open();

		if (count($this->tag_list) > 0) {
			$titles = array();
			foreach ($this->tag_list as $tag)
				$titles[] = $tag->title;

			$header_tag = new SwatHtmlTag('h2');
			$header_tag->setContent(sprintf(
				Pinhole::_('View photos tagged “%s” and:'),
				implode('”, “', $titles)));

			$header_tag->display();
		} elseif ($this->title !== null) {
			$header_tag = new SwatHtmlTag('h2');
			$header_tag->setContent($this->title);
			$header_tag->display();
		}

		$ul_tag = new SwatHtmlTag('ul');
		$ul_tag->id = $this->id.'_list';
		$ul_tag->open();

		$li_tag = new SwatHtmlTag('li');

		foreach ($this->sub_tag_list as $tag) {
			$add_list = clone $this->tag_list;
			$add_list->add($tag);

			$li_tag->open();

			$add_anchor_tag = new SwatHtmlTag('a');
			$add_anchor_tag->class = 'pinhole-tag-list-view-tag';
			$add_anchor_tag->rel = 'tag';
			$add_anchor_tag->href = $this->base.'?'.$add_list->__toString();
			$add_anchor_tag->setContent($tag->title);

			if ($tag->photo_count !== null) {
				$add_anchor_tag->title = sprintf('%s %s',
					SwatString::minimizeEntities(
						SwatString::numberFormat($tag->photo_count)),
					SwatString::minimizeEntities(Pinhole::ngettext(
						'Photo', 'Photos', $tag->photo_count)));
			}

			$add_anchor_tag->display();

			$li_tag->close();

			unset($add_list);
		}

		$ul_tag->close();

		$div_tag->close();
	}

	// }}}
	// {{{ public function setTagList()

	public function setTagList(PinholeTagList $tag_list)
	{
		$this->tag_list = $tag_list;
	}

	// }}}
	// {{{ public function setSubTagList()

	public function setSubTagList(PinholeTagList $tag_list)
	{
		$this->sub_tag_list = $tag_list;
	}

	// }}}
}

?>
