<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Swat/SwatInputControl.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatState.php';
require_once 'Swat/SwatString.php';
require_once 'Pinhole/dataobjects/PinholeTag.php';

/**
 * A tag entry widget for photos 
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoTagEntry extends SwatInputControl implements SwatState
{
	// {{{ public properties

	/**
	 * An array of PinholeTag dataobjects to populate the list
	 *
	 * @var array
	 */
	public $tags = array();

	/**
	 * The array of PinholeTag dataobjects chosen
	 *
	 * @var array
	 */
	public $values = array();

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new tag entry widget
	 *
	 * @param string $id a non-visible unique id for this widget.
	 *
	 * @see SwatWidget::__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);

		$yui = new SwatYUI(array('autocomplete'));

		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addJavaScript('packages/pinhole/javascript/pinhole-photo-tag-entry.js',
			Pinhole::PACKAGE_ID);

		$this->addStyleSheet('packages/pinhole/styles/pinhole-photo-tag-entry.css',
			Pinhole::PACKAGE_ID);
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this textarea
	 *
	 * Outputs an appropriate XHTML tag.
	 */
	public function display()
	{
		if (!$this->visible)
			return;

		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'pinhole-photo-tag-entry';
		$div_tag->id = $this->id.'_wrapper';
		$div_tag->open();

		$input_tag = new SwatHtmlTag('input');
		$input_tag->name = $this->id;
		$input_tag->id = $this->id;

		if (!$this->isSensitive())
			$input_tag->disabled = 'disabled';

		$input_tag->display();

		$container_tag = new SwatHtmlTag('div');
		$container_tag->class = 'pinhole-photo-tag-container';
		$container_tag->id = $this->id.'_container';
		$container_tag->open();

		$div_tag->close();

		
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'pinhole-photo-tag-list';
		$div_tag->open();
		$ul_tag = new SwatHtmlTag('ul');
		$ul_tag->id = $this->id.'_list';
		$ul_tag->open();
		$ul_tag->close();
		$div_tag->close();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ public function process()

	/**
	 * Processes this tag entry
	 *
	 * If a validation error occurs, an error message is attached to this
	 * widget.
	 */
	public function process()
	{
		parent::process();

		$data = &$this->getForm()->getFormData();

		if (!isset($data[$this->id]))
			return;

		$tag_shortnames = $data[$this->id];

		$this->values = array();

		foreach ($this->tags as $tag)
			if (in_array($tag->shortname, $tag_shortnames))
				$this->values[] = $tag;

		if ($this->required && count($this->values) == 0) {
			$message = Swat::_('The %s field is required.');
			$this->addMessage(new SwatMessage($message, SwatMessage::ERROR));
		}
	}

	// }}}
	// {{{ public function getState()

	/**
	 * Gets the current state of this textarea
	 *
	 * @return boolean the current state of this textarea.
	 *
	 * @see SwatState::getState()
	 */
	public function getState()
	{
		return $this->values;
	}

	// }}}
	// {{{ public function setState()

	/**
	 * Sets the current state of this textarea
	 *
	 * @param boolean $state the new state of this textarea.
	 *
	 * @see SwatState::setState()
	 */
	public function setState($state)
	{
		$this->values = $state;
	}

	// }}}
	// {{{ public function getFocusableHtmlId()

	/**
	 * Gets the id attribute of the XHTML element displayed by this widget
	 * that should receive focus
	 *
	 * @return string the id attribute of the XHTML element displayed by this
	 *                 widget that should receive focus or null if there is
	 *                 no such element.
	 *
	 * @see SwatWidget::getFocusableHtmlId()
	 */
	public function getFocusableHtmlId()
	{
		return ($this->visible) ? $this->id : null;
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	/**
	 * Gets the inline JavaScript for this textarea widget
	 *
	 * @return string the inline JavaScript for this textarea widget.
	 */
	protected function getInlineJavaScript()
	{
		$tag_array = array();
		foreach ($this->tags as $tag)
			$tag_array[] = sprintf("[%s, %s]\n",
				SwatString::quoteJavaScriptString($tag->title),
				SwatString::quoteJavaScriptString($tag->shortname));

		$value_array = array();
		foreach ($this->values as $tag)
			$value_array[] = sprintf("[%s, %s]\n",
				SwatString::quoteJavaScriptString($tag->title),
				SwatString::quoteJavaScriptString($tag->shortname));

		return sprintf("var %1\$s_obj = new PinholePhotoTagEntry('%1\$s');
			%1\$s_obj.tag_array = [%2\$s];
			%1\$s_obj.value_array = [%3\$s];",
			$this->id, implode(',', $tag_array),
			implode(',', $value_array));
	}

	// }}}
}

?>
