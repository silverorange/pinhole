<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Swat/SwatImageCellRenderer.php';

/**
 * A renderer for photo checkboxes
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoCellRenderer extends SwatImageCellRenderer
{
	// {{{ public properties

	public $photo;
	public $link;
	public $link_value;

	// }}}
	// {{{ public function render()

	public function render()
	{
		if (!$this->visible)
			return;

		if ($this->link !== null) {
			$a_tag = new SwatHtmlTag('a');
			$a_tag->href = sprintf($this->link, $this->link_value);
			$a_tag->open();
		}

		$this->image = $this->getUri();
		$this->width = $this->photo->getDimension('thumb')->width;
		$this->height = $this->photo->getDimension('thumb')->height;
		$this->occupy_width = $this->photo->getDimension('thumb')->dimension->max_width;
		$this->occupy_height = $this->photo->getDimension('thumb')->dimension->max_height;

		parent::render();

		$title_tag = new SwatHtmlTag('span');
		if ($this->photo->title !== null) {
			$title_tag->setContent(SwatString::condense($this->photo->title, 30));
		}
		$title_tag->display();

		if ($this->link !== null)
			$a_tag->close();
	}

	// }}}
	// {{{ protected function getUri()

	protected function getUri()
	{
		return $this->photo->getDimension('thumb')->getURI();
	}

	// }}}
}

?>
