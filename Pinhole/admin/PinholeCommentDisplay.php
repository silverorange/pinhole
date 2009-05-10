<?php

require_once 'Swat/SwatControl.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatYUI.php';
require_once 'Pinhole/Pinhole.php';
require_once 'XML/RPCAjax.php';

/**
 * Displays a comment with optional buttons to edit, set published status
 * delete and mark as spam
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeCommentDisplay extends SwatControl
{
	// {{{ protected properties

	/**
	 * @var PinholeComment
	 *
	 * @see PinholeCommentDisplay::setComment()
	 */
	protected $comment;

	/**
	 * @var SiteApplication
	 *
	 * @see PinholeCommentDisplay::setApplication()
	 */
	protected $app;

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->html_head_entry_set->addEntrySet(
			XML_RPCAjax::getHtmlHeadEntrySet());

		$yui = new SwatYUI(array('dom', 'event', 'animation'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addStyleSheet(
			'packages/pinhole/admin/styles/pinhole-comment-display.css',
			Pinhole::PACKAGE_ID);

		$this->addJavaScript(
			'packages/pinhole/admin/javascript/pinhole-comment-display.js',
			Pinhole::PACKAGE_ID);
	}

	// }}}
	// {{{ public function setComment()

	public function setComment(PinholeComment $comment)
	{
		$this->comment = $comment;
	}

	// }}}
	// {{{ public function setApplication()

	public function setApplication(SiteApplication $app)
	{
		$this->app = $app;
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		if ($this->comment === null)
			return;

		if ($this->app === null)
			return;

		parent::display();

		$container_div = new SwatHtmlTag('div');
		$container_div->class = $this->getCSSClassString();
		$container_div->id = $this->id;
		$container_div->open();

		$animation_container = new SwatHtmlTag('div');
		$animation_container->class = 'pinhole-comment-display-content';
		$animation_container->open();

		$this->displayControls();
		$this->displayHeader();
		$this->displayComment();

		$animation_container->close();
		$container_div->close();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function displayComment()

	protected function displayComment()
	{
		$strong_tag = new SwatHtmlTag('strong');
		if ($this->comment->photographer !== null) {
			$strong_tag->setContent($this->comment->photographer->fullname);
		} else {
			$strong_tag->setContent($this->comment->fullname);
		}

		$strong_tag->display();

		$p_tag = new SwatHtmlTag('p');
		$p_tag->setContent(SwatString::condense($this->comment->bodytext),
			'text/xml');

		$p_tag->display();
	}

	// }}}
	// {{{ protected function displayControls()

	protected function displayControls()
	{
		$controls_div = new SwatHtmlTag('div');
		$controls_div->id = $this->id.'_controls';
		$controls_div->class = 'pinhole-comment-display-controls';
		$controls_div->open();
		$controls_div->close();
	}

	// }}}
	// {{{ protected function displayHeader()

	protected function displayHeader()
	{
		$header_div = new SwatHtmlTag('div');
		$header_div->class = 'pinhole-comment-display-header';
		$header_div->open();

		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = sprintf('Photo/Edit?id=%s',
			$this->comment->photo->id);

		$anchor_tag->setContent($this->comment->photo->getTitle());
		echo sprintf(Pinhole::_('Comment on %s'), $anchor_tag);

		$this->displayStatusSpan();

		$header_div->close();
	}

	// }}}
	// {{{ protected function displayStatusSpan()

	protected function displayStatusSpan()
	{
		$status_span = new SwatHtmlTag('span');
		$status_span->id = $this->id.'_status';
		$status_spam->class = 'pinhole-comment-display-status';
		$status_span->open();

		if ($this->comment->spam) {
			echo ' - ', Pinhole::_('Spam');
		} else {
			switch ($this->comment->status) {
			case SiteComment::STATUS_UNPUBLISHED:
				echo ' - ', Pinhole::_('Unpublished');
				break;

			case SiteComment::STATUS_PENDING:
				echo ' - ', Pinhole::_('Pending');
				break;
			}
		}

		$status_span->close();
	}

	// }}}
	// {{{ protected function getCSSClassNames()

	/**
	 * Gets the array of CSS classes that are applied to this comment display
	 *
	 * @return array the array of CSS classes that are applied to this comment
	 *                display.
	 */
	protected function getCSSClassNames()
	{
		$classes = array('pinhole-comment-display');
		$classes = array_merge($classes, parent::getCSSClassNames());
		$classes[] = $this->getVisibilityCssClassName();
		return $classes;
	}

	// }}}
	// {{{ protected function getVisibilityCssClassName()

	protected function getVisibilityCssClassName()
	{
		if ($this->comment->spam) {
			$class = 'pinhole-comment-red';
		} else {
			switch ($this->comment->status) {
			case SiteComment::STATUS_UNPUBLISHED:
				$class = 'pinhole-comment-red';
				break;

			case SiteComment::STATUS_PENDING:
				$class = 'pinhole-comment-yellow';
				break;

			case SiteComment::STATUS_PUBLISHED:
			default:
				$class = 'pinhole-comment-green';
				break;
			}
		}

		return $class;
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	/**
	 * Gets the inline JavaScript required by this control
	 *
	 * @return string the inline JavaScript required by this control.
	 */
	protected function getInlineJavaScript()
	{
		static $shown = false;

		if (!$shown) {
			$javascript = $this->getInlineJavaScriptTranslations();
			$shown = true;
		} else {
			$javascript = '';
		}

		$spam = ($this->comment->spam) ? 'true' : 'false';
		$status = $this->comment->status;

		$javascript.= sprintf(
			"var %s_obj = new PinholeCommentDisplay('%s', %s, %s);",
			$this->id, $this->id, $status, $spam);

		return $javascript;
	}

	// }}}
	// {{{ protected function getInlineJavaScriptTranslations()

	/**
	 * Gets translatable string resources for the JavaScript object for
	 * this widget
	 *
	 * @return string translatable JavaScript string resources for this widget.
	 */
	protected function getInlineJavaScriptTranslations()
	{
		$approve_text = SwatString::quoteJavaScriptString(Pinhole::_('Approve'));
		$deny_text    = SwatString::quoteJavaScriptString(Pinhole::_('Deny'));
		$publish_text = SwatString::quoteJavaScriptString(Pinhole::_('Publish'));
		$spam_text    = SwatString::quoteJavaScriptString(Pinhole::_('Spam'));
		$delete_text  = SwatString::quoteJavaScriptString(Pinhole::_('Delete'));
		$cancel_text  = SwatString::quoteJavaScriptString(Pinhole::_('Cancel'));

		$not_spam_text = SwatString::quoteJavaScriptString(
			Pinhole::_('Not Spam'));

		$unpublish_text = SwatString::quoteJavaScriptString(
			Pinhole::_('Unpublish'));

		$status_spam_text = SwatString::quoteJavaScriptString(
			Pinhole::_('Spam'));

		$status_pending_text = SwatString::quoteJavaScriptString(
			Pinhole::_('Pending'));

		$status_unpublished_text  = SwatString::quoteJavaScriptString(
			Pinhole::_('Unpublished'));

		$delete_confirmation_text  = SwatString::quoteJavaScriptString(
			Pinhole::_('Delete comment?'));

		return
			"PinholeCommentDisplay.approve_text   = {$approve_text};\n".
			"PinholeCommentDisplay.deny_text      = {$deny_text};\n".
			"PinholeCommentDisplay.publish_text   = {$publish_text};\n".
			"PinholeCommentDisplay.unpublish_text = {$unpublish_text};\n".
			"PinholeCommentDisplay.spam_text      = {$spam_text};\n".
			"PinholeCommentDisplay.not_spam_text  = {$not_spam_text};\n".
			"PinholeCommentDisplay.delete_text    = {$delete_text};\n".
			"PinholeCommentDisplay.cancel_text    = {$cancel_text};\n\n".
			"PinholeCommentDisplay.status_spam_text        = ".
				"{$status_spam_text};\n".
			"PinholeCommentDisplay.status_pending_text     = ".
				"{$status_pending_text};\n".
			"PinholeCommentDisplay.status_unpublished_text = ".
				"{$status_unpublished_text};\n\n".
			"PinholeCommentDisplay.delete_confirmation_text = ".
				"{$delete_confirmation_text};\n\n";
	}

	// }}}
}

?>
