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
class PinholePhotoNextPrev extends SwatControl
{
	public $base = 'tag';

	protected $tag_list;

	protected $photo;

	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addStyleSheet(
			'packages/pinhole/styles/pinhole-photo-next-prev.css',
			Pinhole::PACKAGE_ID);
	}

	public function display()
	{
		if (!$this->visible)
			return;

		if ($this->tag_list === null || $this->photo === null)
			return;

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

	public function setTagList(PinholeTagList $tag_list)
	{
		$this->tag_list = $tag_list;
	}

	public function setPhoto(PinholePhoto $photo)
	{
		$this->photo = $photo;
	}

	protected function displayPrev(PinholePhoto $photo = null)
	{
		if ($photo === null) {
			$span_tag = new SwatHtmlTag('span');
			$span_tag->setContent(Pinhole::_('Prev'));
			$span_tag->display();
		} else {
			$tag_path = (count($this->tag_list) == 0) ?
				'' : $this->tag_list->__toString();

			$a_tag = new SwatHtmlTag('a');
			$a_tag->href = sprintf('photo/%s?%s',
				$photo->id, $tag_path);

			$a_tag->title = $photo->title;
			$a_tag->class = 'prev';
			$a_tag->setContent(Pinhole::_('Prev'));
			$a_tag->display();
		}
	}

	protected function displayCurrent(PinholePhoto $photo = null)
	{
		echo ' ';

		$tag_path = (count($this->tag_list) == 0) ?
			'' : $this->tag_list->__toString();

		$a_tag = new SwatHtmlTag('a');
		$a_tag->setContent(Pinhole::_('Thumbnails'));
		$a_tag->href = 'tag?'.$tag_path;
		$a_tag->class = 'view-all';
		$a_tag->display();

		echo ' ';
	}

	protected function displayNext(PinholePhoto $photo = null)
	{
		if ($photo === null) {
			$span_tag = new SwatHtmlTag('span');
			$span_tag->setContent(Pinhole::_('Next'));
			$span_tag->display();
		} else {
			$tag_path = (count($this->tag_list) == 0) ?
				'' : $this->tag_list->__toString();

			$a_tag = new SwatHtmlTag('a');
			$a_tag->href = sprintf('photo/%s?%s',
				$photo->id, $tag_path);

			$a_tag->title = $photo->title;
			$a_tag->class = 'next';
			$a_tag->setContent(Pinhole::_('Next'));
			$a_tag->display();
		}
	}
}

?>
