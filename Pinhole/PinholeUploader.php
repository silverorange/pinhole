<?php

require_once 'Pinhole/Pinhole.php';

require_once 'Swat/SwatForm.php';
require_once 'Swat/SwatFileEntry.php';
require_once 'Swat/SwatProgressBar.php';
require_once 'Swat/SwatButton.php';
require_once 'Swat/SwatYUI.php';
require_once 'Swat/exceptions/SwatException.php';

require_once 'XML/RPCAjax.php';

/**
 * Widget for uploading files asynchronously with an upload progress bar
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @todo      test JavaScript progressive-enhancement support
 */
class PinholeUploader extends SwatFileEntry
{
	// {{{ public properties

	public $action = '#';

	public $target_action;

	/**
	 * The title of the upload button for this uploader
	 *
	 * Defaults to 'Upload'.
	 *
	 * @var String
	 */
	public $title;

	// }}}
	// {{{ private properties

	/**
	 * @var SwatProgressBar
	 */
	private $progress_bar; 

	/**
	 * @var boolean
	 */
	private $widgets_created = false;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new uploader
	 *
	 * @param string $id a non-visible unique id for this widget.
	 *
	 * @see SwatWidget::__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->requires_id = true;

		$yui = new SwatYUI(array('event', 'animation'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$ajax = new XML_RPCAjax();
		$this->html_head_entry_set->addEntrySet($ajax->getHtmlHeadEntrySet());

		$this->addJavaScript('packages/pinhole/javascript/uploader.js',
			Pinhole::PACKAGE_ID);

		$this->addStyleSheet('packages/pinhole/styles/uploader.css',
			Pinhole::PACKAGE_ID);

		$this->title = Pinhole::_('Upload');
	}

	// }}}
	// {{{ public function getForm()

	public function getForm()
	{
		return null;
	}

	// }}}
	// {{{ public function init()

	public function init()
	{
		parent::init();

		if ($this->getFirstAncestor('SwatForm') !== null)
			throw new SwatException('Uploader cannot reside inside a SwatForm');
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		$this->createEmbeddedWidgets();

		$form_tag = new SwatHtmlTag('form');
		$form_tag->id = $this->id.'_form';
		$form_tag->class = 'swat-form';
		$form_tag->method = 'post';
		$form_tag->action = $this->action;
		$form_tag->enctype = 'multipart/form-data';
		$form_tag->open();

		$hidden_input_tag = new SwatHtmlTag('input');
		$hidden_input_tag->type = 'hidden';
		$hidden_input_tag->id = $this->id.'_identifier';
		$hidden_input_tag->name = 'UPLOAD_IDENTIFIER';
		$hidden_input_tag->value = $this->id.'_'.uniqid();
		$hidden_input_tag->display();

		parent::display();

		echo '&nbsp;';

		$button_input_tag = new SwatHtmlTag('input');
		$button_input_tag->type = 'submit';
		$button_input_tag->id = $this->id.'_button';
		$button_input_tag->class = 'uploader-submit-button';
		$button_input_tag->value = $this->title;
		$button_input_tag->display();

		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'uploader-progress-bar';
		$div_tag->open();
		$this->progress_bar->display();
		$div_tag->close();

		$form_tag->close();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ public function getHtmlHeadEntrySet()

	/**
	 * Gets the SwatHtmlHeadEntry objects needed by this uploader
	 *
	 * @return SwatHtmlHeadEntrySet the SwatHtmlHeadEntry objects needed by
	 *                               this uploader.
	 *
	 * @see SwatUIObject::getHtmlHeadEntrySet()
	 */
	public function getHtmlHeadEntrySet()
	{
		$this->createEmbeddedWidgets();
		$set = parent::getHtmlHeadEntrySet();
		$set->addEntrySet($this->progress_bar->getHtmlHeadEntrySet());
		return $set;
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		$action = ($this->target_action === null) ?
			$this->action : $this->target_action;

		$progress_bar_object_id = $this->id.'_progress_bar_obj';
		return sprintf("%s_obj = new UploadClient('%s', '%s', %s);",
			$this->id, $this->id, $action, $progress_bar_object_id);
	}

	// }}}
	// {{{ private function createEmbeddedWidgets()

	/**
	 * Creates all internal widgets required for this uploader
	 */
	private function createEmbeddedWidgets()
	{ 
		if (!$this->widgets_created) {
			$this->progress_bar =
				new SwatProgressBar($this->id.'_progress_bar');

			$this->progress_bar->text = sprintf(Pinhole::_('%s%% complete'),
				'0');

			$this->widgets_created = true;
		}
	}

	// }}}
}

?>
