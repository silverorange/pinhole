<?php

require_once 'Site/admin/components/Comment/AjaxServer.php';
require_once 'Pinhole/dataobjects/PinholeComment.php';

/**
 * Performs actions on comments via AJAX
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeCommentAjaxServer extends SiteCommentAjaxServer
{
	// {{{ protected function getPermalink()

	protected function getPermalink(SiteComment $comment)
	{
		return $this->app->getFrontendBaseHref().
			$this->app->config->pinhole->path.'photo'.$comment->photo->id;
	}

	// }}}
	// {{{ protected function flushCache()

	protected function flushCache()
	{
		if (isset($this->app->memcache)) {
			$this->app->memcache->flushNs('photos');
		}
	}

	// }}}
	// {{{ protected function getComment()

	protected function getComment($comment_id)
	{
		$comment_class = SwatDBClassMap::get('PinholeComment');
		$comment = new $comment_class();
		$comment->setDatabase($this->app->db);
		if ($comment->load($comment_id, $this->app->getInstance())) {
			return $comment;
		} else {
			return null;
		}
	}

	// }}}
}

?>
