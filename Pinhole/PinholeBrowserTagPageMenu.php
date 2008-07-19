<?php

require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/PinholeTagList.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'Swat/SwatControl.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeBrowserTagPageMenu extends SwatControl
{
	// {{{ public properties

	public $base = '';

	// }}}
	// {{{ protected properties

	protected $tag_list;

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
		$div_tag->id = $this->id;
		$div_tag->class = 'pinhole-browser-page-menu';
		$div_tag->open();

		$a_tag = new SwatHtmlTag('a');
		$a_tag->href = $this->appendTagPath(
			sprintf('%stags/alphabetical', $this->base));
		$a_tag->setContent(Pinhole::_('Alphabetical'));
		$a_tag->display();

		$a_tag = new SwatHtmlTag('a');
		$a_tag->href = $this->appendTagPath(
			sprintf('%stags/date', $this->base));
		$a_tag->setContent(Pinhole::_('By Date Added'));
		$a_tag->display();

		$a_tag = new SwatHtmlTag('a');
		$a_tag->href = $this->appendTagPath(
			sprintf('%stags/popular', $this->base));
		$a_tag->setContent(Pinhole::_('By Popularity'));
		$a_tag->display();

		$a_tag = new SwatHtmlTag('a');
		$a_tag->href = $this->appendTagPath(
			sprintf('%stags/cloud', $this->base));
		$a_tag->setContent(Pinhole::_('Cloud View'));
		$a_tag->display();

		$div_tag->close();
	}

	// }}}
	// {{{ public function setTagList()

	public function setTagList(PinholeTagList $tag_list)
	{
		$this->tag_list = $tag_list;
	}

	// }}}
	// {{{ private function appendTagPath()

	private function appendTagPath($uri)
	{
		if (count($this->tag_list) > 0)
			$uri.= '?'.$this->tag_list->__toString();

		return $uri;
	}

	// }}}
}

?>
