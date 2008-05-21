<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Swat/exceptions/SwatException.php';
require_once 'Swat/exceptions/SwatInvalidClassException.php';
require_once 'Swat/SwatInputControl.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatState.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatMessage.php';
require_once 'Swat/SwatYUI.php';
require_once 'Pinhole/PinholeTagList.php';
require_once 'Pinhole/dataobjects/PinholeTagDataObjectWrapper.php';
require_once 'Pinhole/tags/PinholeTag.php';

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
	 * Application used by this tag entry control
	 *
	 * @var SiteWebApplication
	 */
	private $app;

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
			'packages/pinhole/admin/javascript/pinhole-photo-tag-entry.js',
			Pinhole::PACKAGE_ID);

		$this->addStyleSheet(
			'packages/pinhole/admin/styles/pinhole-photo-tag-entry.css',
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

		SwatWidget::display();

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
		// List left blank. Values are filled in via javascript.
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
	 *
	 * @throws SwatException if no database connection is set on this tag
	 *                        entry control.
	 */
	public function process()
	{
		parent::process();

		if ($this->app === null)
			throw new SwatException(
				'An application must be set on the tag entry control during '.
				'the widget init phase.');

		$this->selected_tag_list = $this->tag_list->getEmptyCopy();

		$data = &$this->getForm()->getFormData();
		$new_key = $this->id.'_new';
		if (isset($data[$new_key]) && is_array($data[$new_key]))
			foreach ($data[$new_key] as $new_tag)
				$this->insertTag($new_tag);

		if (isset($data[$this->id]) && is_array($data[$this->id])) {
			$tag_strings = $data[$this->id];

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
	// {{{ public function setApplication()

	/**
	 * Sets the application used by this tag entry control
	 *
	 * @param SiteWebApplication $app the application to use.
	 */
	public function setApplication(SiteWebApplication $app)
	{
		$this->app = $app;
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
	// {{{ public function setSelectedTagList()

	/**
	 * Sets the list of tags that are pre-selected for this photo
	 *
	 * @param PinholeTagList $tag_list the list of tags that appear
	 *                       pre-selected for this entry widget.
 	 */
	public function setSelectedTagList(PinholeTagList $tag_list)
	{
		$this->selected_tag_list = $tag_list;
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
	// {{{ protected function insertTag()

	/**
	 * Creates a new tag
	 *
	 * @throws SwatException if no database connection is set on this tag
	 *                        entry control.
	 */
	protected function insertTag($title)
	{
		if ($this->app === null)
			throw new SwatException(
				'An application must be set on the tag entry control during '.
				'the widget init phase.');

		// check to see if the tag already exists
		$instance_id = $this->app->getInstanceId();
		$sql = sprintf('select * from
			PinholeTag where title = %s and instance %s %s',
			$this->app->db->quote($title, 'text'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$tags = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('PinholeTagDataObjectWrapper'));

		// only insert if no tag already exists (prevents creating two tags on
		// reloading)
		if (count($tags) > 0) {
			$tag_obj = $tags->getFirst();
		} else {
			$tag_obj = new PinholeTagDataObject();
			$tag_obj->setDatabase($this->app->db);
			$tag_obj->instance = $instance_id;
			$tag_obj->title = $title;
			$tag_obj->save();
		}

		$tag = new PinholeTag($tag_obj);
		$this->tag_list->add($tag);
		$this->selected_tag_list->add($tag);

		$message = new SwatMessage(
			sprintf(Pinhole::_('“%s” tag has been added'),
				SwatString::minimizeEntities($tag->getTitle())));

		$message->content_type = 'text/xml';
		$message->secondary_content = sprintf(Pinhole::_(
			'You can <a href="Tag/Edit?id=%d">edit this tag</a> to customize it.'),
			$tag_obj->id);

		$this->app->messages->add($message);
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
