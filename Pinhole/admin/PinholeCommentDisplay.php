<?php

require_once 'Site/admin/SiteCommentDisplay.php';

/**
 * Displays a comment with optional buttons to edit, set published status
 * delete and mark as spam
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeCommentDisplay extends SiteCommentDisplay
{
	// {{{ protected function displayHeader()

	protected function displayHeader()
	{
		$header_div = new SwatHtmlTag('div');
		$header_div->class = 'site-comment-display-header';
		$header_div->open();

		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = sprintf('Photo/Comments?id=%s',
			$this->comment->photo->id);

		$anchor_tag->setContent($this->comment->photo->getTitle(true));

		printf(Pinhole::_('Comment on %s'), $anchor_tag);

		$this->displayStatusSpan();

		echo ' - ';

		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = sprintf('Comment/Edit?id=%s',
			$this->comment->id);

		$anchor_tag->setContent(Pinhole::_('Edit'));
		$anchor_tag->display();

		$header_div->close();
	}

	// }}}
}

?>
