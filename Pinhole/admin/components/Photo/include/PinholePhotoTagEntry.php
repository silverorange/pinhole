<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Swat/exceptions/SwatException.php';
require_once 'Swat/exceptions/SwatInvalidClassException.php';
require_once 'Swat/SwatInputControl.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatState.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatYUI.php';
require_once 'Pinhole/PinholeTagList.php';

/**
 * Control for selecting multiple tags from a list of tags
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoTagEntry extends SwatInputControl implements SwatState
{
	// {{{ private properties

	/**
	 * The list of tag objects possible to select with this control
	 *
	 * @var PinholeTagList
	 *
	 * @see PinholeTagEntry::setTagList()
	 */
	private $tag_list;

	/**
	 * The list of tag objects selected by this tag entry control
	 *
	 * @var PinholeTagList
	 */
	private $selected_tag_list;

	/**
	 * Database connection used by this tag entry control
	 *
	 * @var MDB2_Driver_Common
	 */
	private $db;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new tag entry control 
	 *
	 * @param string $id a non-visible unique id for this widget.
	 *
	 * @see SwatWidget::__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->requires_id = true;

		$yui = new SwatYUI(array('autocomplete'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addJavaScript(
			'packages/pinhole/javascript/pinhole-photo-tag-entry.js',
			Pinhole::PACKAGE_ID);

		$this->addStyleSheet(
			'packages/pinhole/styles/pinhole-photo-tag-entry.css',
			Pinhole::PACKAGE_ID);
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this tag entry control 
	 */
	public function display()
	{
		if (!$this->visible)
			return;

		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'pinhole-photo-tag-entry';
		$div_tag->id = $this->id;
		$div_tag->open();

		$input_tag = new SwatHtmlTag('input');
		$input_tag->name = $this->id.'_value';
		$input_tag->id = $this->id.'_value';

		if (!$this->isSensitive())
			$input_tag->disabled = 'disabled';

		$input_tag->display();

		$container_tag = new SwatHtmlTag('div');
		$container_tag->class = 'pinhole-photo-tag-container';
		$container_tag->id = $this->id.'_container';
		$container_tag->setContent('');
		$container_tag->display();

		$ul_tag = new SwatHtmlTag('ul');
		$ul_tag->id = $this->id.'_list';
		$ul_tag->class = 'pinhole-photo-tag-list';
		$ul_tag->open();

		if ($this->selected_tag_list !== null) {
			foreach ($this->selected_tag_list as $tag) {
				$li_tag = new SwatHtmlTag('li');
				$li_tag->setContent($tag->getTitle());
				$li_tag->display();
			}
		}

		$ul_tag->close();

		$div_tag->close();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ public function process()

	/**
	 * Processes this tag entry control
	 *
	 * If a validation error occurs, an error message is attached to this
	 * widget.
	 */
	public function process()
	{
		parent::process();

		if ($this->db === null)
			throw new SwatException(
				'A database must be set on the tag entry control during '.
				'the widget init phase.');

		$data = &$this->getForm()->getFormData();
		if (isset($data[$this->id]) && is_array($data[$this->id])) {
			$tag_strings = $data[$this->id];

			$this->selected_tag_list = new PinholeTagList($this->db);

			// make sure entered tags are in the original tag list
			foreach ($tag_strings as $tag_string)
				if ($this->tag_list->contains($tag_string))
					$this->selected_tag_list->add(
						$this->tag_list->get($tag_string));
		}

		if ($this->required && count($this->selected_tag_list) == 0) {
			$message = Swat::_('The %s field is required.');
			$this->addMessage(new SwatMessage($message, SwatMessage::ERROR));
		}
	}

	// }}}
	// {{{ public function getState()

	/**
	 * Gets the current state of this tag entry control
	 *
	 * @return boolean the current state of this tag entry control.
	 *
	 * @see SwatState::getState()
	 */
	public function getState()
	{
		return $this->getSelectedTagList();
	}

	// }}}
	// {{{ public function setState()

	/**
	 * Sets the current state of this tag entry control 
	 *
	 * @param PinholeTagList $state the new state of this tag entry control.
	 *
	 * @see SwatState::setState()
	 *
	 * @throws SwatInvalidClassException if the given state is not a
	 *                                    {@link PinholeTagList}.
	 */
	public function setState($state)
	{
		if ($state instanceof PinholeTagList)
			$this->selected_tag_list = $this->tag_list->intersect($state);
		else
			throw new SwatInvalidClassException(
				'State must be a PinholeTagList.', 0, $state);
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
		return ($this->visible) ? $this->id.'_value' : null;
	}

	// }}}
	// {{{ public function setDatabase()

	/**
	 * Sets the database connection used by this tag entry control
	 *
	 * A database connection must be set before this control can be processed
	 * correctly. Set the database in the widget init phase.
	 *
	 * @param MDB2_Driver_Common $db the database connection to use.
	 */
	public function setDatabase(MDB2_Driver_Common $db)
	{
		$this->db = $db;
	}

	// }}}
	// {{{ public function setTagList()

	/**
	 * Sets the list of tags that may be selected by this tag entry control
	 *
	 * @param PinholeTagList $tag_list the list of tags that may be selected by
	 *                                  this tag entry control.
	 */
	public function setTagList(PinholeTagList $tag_list)
	{
		$this->tag_list = $tag_list;
	}

	// }}}
	// {{{ public function getSelectedTagList()

	/**
	 * Gets the list of tags selected by this tag entry control
	 *
	 * Call this method after processing this control to get the tags selected
	 * by the user.
	 *
	 * @return PinholeTagList the list of tags selected by this tag entry
	 *                         control.
	 */
	public function getSelectedTagList()
	{
		return $this->selected_tag_list;
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	/**
	 * Gets the inline JavaScript for this tag entry control 
	 *
	 * @return string the inline JavaScript for this tag entry control.
	 */
	protected function getInlineJavaScript()
	{
		$tag_array = array();
		foreach ($this->tag_list as $tag)
			$tag_array[] = sprintf("\n[%s, %s]",
				SwatString::quoteJavaScriptString($tag->getTitle()),
				SwatString::quoteJavaScriptString($tag->__toString()));

		$value_array = array();
		if ($this->selected_tag_list !== null) {
			foreach ($this->selected_tag_list as $tag)
				$value_array[] =
					SwatString::quoteJavaScriptString($tag->__toString());
		}

		return sprintf("var %1\$s_obj = new PinholePhotoTagEntry(".
			"'%1\$s', [%2\$s], [%3\$s]);",
			$this->id,
			implode(',', $tag_array),
			implode(',', $value_array));
	}

	// }}}
}

?>
