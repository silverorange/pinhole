<?php

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoNextPrev extends SwatControl
{
	// {{{ public properties

	public $base = '';

	// }}}
	// {{{ protected properties

	protected $tag_list;

	protected $photo;

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		if ($this->tag_list === null || $this->photo === null)
			return;

		parent::display();

		$photos = $this->tag_list->getNextPrevPhotos($this->photo);

		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = $this->id;
		$div_tag->class = 'pinhole-photo-next-prev';
		$div_tag->open();

		$this->displayPrev($photos['prev']);
		$this->displayCurrent($this->photo);
		$this->displayNext($photos['next']);

		$div_tag->close();
	}

	// }}}
	// {{{ public function setTagList()

	public function setTagList(PinholeTagList $tag_list)
	{
		$this->tag_list = $tag_list;
	}

	// }}}
	// {{{ public function setPhoto()

	public function setPhoto(PinholePhoto $photo)
	{
		$this->photo = $photo;
	}

	// }}}
	// {{{ protected function displayPrev()

	protected function displayPrev(PinholePhoto $photo = null)
	{
		if ($photo === null) {
			$span_tag = new SwatHtmlTag('span');
			$span_tag->class = 'prev';
			$span_tag->setContent(Pinhole::_('Prev'));
			$span_tag->display();
		} else {
			$href = $this->appendTagPath(
				sprintf('%sphoto/%s', $this->base, $photo->id));

			$a_tag = new SwatHtmlTag('a');
			$a_tag->href = $href;
			$a_tag->title = $photo->title;
			$a_tag->class = 'prev';
			$a_tag->setContent(Pinhole::_('Prev'));
			$a_tag->display();

			$this->html_head_entry_set->addEntry(
				new SwatLinkHtmlHeadEntry($href, 'prefetch', null,
					null, Pinhole::PACKAGE_ID));

			$this->html_head_entry_set->addEntry(
				new SwatLinkHtmlHeadEntry($photo->getUri('large'),
					'prefetch', null,
					null, Pinhole::PACKAGE_ID));

			$this->html_head_entry_set->addEntry(
				new SwatLinkHtmlHeadEntry($href, 'prev', null,
					$photo->title, Pinhole::PACKAGE_ID));
		}
	}

	// }}}
	// {{{ protected function displayCurrent()

	protected function displayCurrent(PinholePhoto $photo = null)
	{
		$href = $this->appendTagPath($this->base.'tag', $photo->id);

		$a_tag = new SwatHtmlTag('a');
		$a_tag->setContent(Pinhole::_('Thumbnails'));
		$a_tag->href = $href;
		$a_tag->class = 'view-all';
		$a_tag->display();

		$this->html_head_entry_set->addEntry(
			new SwatLinkHtmlHeadEntry($href, 'index', null,
				null, Pinhole::PACKAGE_ID));
	}

	// }}}
	// {{{ protected function displayNext()

	protected function displayNext(PinholePhoto $photo = null)
	{
		if ($photo === null) {
			$span_tag = new SwatHtmlTag('span');
			$span_tag->class = 'next';
			$span_tag->setContent(Pinhole::_('Next'));
			$span_tag->display();
		} else {
			$a_tag = new SwatHtmlTag('a');
			$href = $this->appendTagPath(
				sprintf('%sphoto/%s', $this->base, $photo->id));

			$a_tag->href = $href;
			$a_tag->title = $photo->title;
			$a_tag->class = 'next';
			$a_tag->setContent(Pinhole::_('Next'));
			$a_tag->display();

			$this->html_head_entry_set->addEntry(
				new SwatLinkHtmlHeadEntry($href, 'prefetch', null,
					null, Pinhole::PACKAGE_ID));

			$this->html_head_entry_set->addEntry(
				new SwatLinkHtmlHeadEntry($photo->getUri('large'),
					'prefetch', null,
					null, Pinhole::PACKAGE_ID));

			$this->html_head_entry_set->addEntry(
				new SwatLinkHtmlHeadEntry($href, 'next', null,
					$photo->title, Pinhole::PACKAGE_ID));
		}
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
