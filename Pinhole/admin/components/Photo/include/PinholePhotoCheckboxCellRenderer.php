<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Swat/SwatCheckboxCellRenderer.php';

/**
 * A renderer for photo checkboxes
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoCheckboxCellRenderer extends SwatCheckboxCellRenderer
{
	// {{{ public function render()

	public function render()
	{
		static $count = 0;

		if (!$this->visible)
			return;

		$count++;

		$input_tag = new SwatHtmlTag('input');
		$input_tag->type = 'checkbox';
		$input_tag->name = $this->id.'[]';
		$input_tag->value = $this->value;
		$input_tag->id = $this->id.'-'.$count;

		if (!$this->sensitive)
			$input_tag->disabled = 'disabled';

		if (isset($_POST[$this->id]))
			if (in_array($this->value, $_POST[$this->id]))
				$input_tag->checked = 'checked';

		$label_tag = new SwatHtmlTag('label');
		$label_tag->for = $this->id.'-'.$count;
		$label_tag->open();

		$input_tag->display();

		echo Pinhole::_('select photo');

		$label_tag->close();
	}

	// }}}
}

?>
