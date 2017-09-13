<?php

/**
 * View for Pinhole comment objects
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeCommentView extends SiteCommentView
{
	// {{{ protected properties

	protected $tag_list;

	// }}}
	// {{{ public function setTagList()

	public function setTagList(PinholeTagList $tag_list)
	{
		$this->tag_list = $tag_list;
	}

	// }}}
	// {{{ protected function getRelativeUri()

	protected function getRelativeUri(SiteComment $comment)
	{
		$uri = $this->app->config->pinhole->path;

		$uri.= 'photo/'.$comment->photo->id;
		if (count($this->tag_list) > 0)
			$uri.= '?'.$this->tag_list->__toString();

		$uri.= '#comment'.$comment->id;

		return $uri;
	}

	// }}}

	// part display methods
	// {{{ protected function displayAuthor()

	protected function displayAuthor(SiteComment $comment)
	{
		if ($this->getMode('author') > SiteView::MODE_NONE) {
			if ($comment->photographer === null) {
				parent::displayAuthor($comment);
			} else {
				// System author
				$span_tag = new SwatHtmlTag('span');
				$span_tag->class = 'comment-author system-comment-author';
				$span_tag->setContent($comment->photographer->fullname);
				$span_tag->display();
			}
		}
	}

	// }}}
}

?>
