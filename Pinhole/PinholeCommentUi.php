<?php

require_once 'Site/SiteCommentUi.php';

/**
 * Pinhole comment UI
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeCommentUi extends SiteCommentUi
{
	// {{{ protected function getThankYouUri()

	protected function getThankYouUri()
	{
		// skip the first GET var to keep from conflicting with tags
		if (strpos($this->source, '?') === false)
			return $this->source.'?&'.self::THANK_YOU_ID;
		else
			return $this->source.'&'.self::THANK_YOU_ID;
	}

	// }}}
	// {{{ protected function getCommentStatus()

	protected function getCommentStatus()
	{
		$global_status = $this->app->config->pinhole->global_comment_status;

		if ($global_status === null) {
			return $this->post->getCommentStatus();
		} elseif ($global_status == true) {
			// comments are globally turned on
			return $this->app->config->pinhole->default_comment_status;
		} else {
			// comments are globally turned off
			return SiteCommentStatus::CLOSED;
		}
	}

	// }}}
	// {{{ protected function setCommentPost()

	protected function setCommentPost(SiteComment $comment,
		SiteCommentStatus $post)
	{
		$comment->photo = $post;
	}

	// }}}
	// {{{ protected function getPermalink()

	protected function getPermalink(SiteComment $comment)
	{
		return $this->app->config->pinhole->path.
			'photo/'.$comment->photo->id;
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue()
	{
		$type = NateGoSearch::getDocumentType($this->app->db, 'comment');

		if ($type === null)
			return;

		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->comment->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->comment->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function clearCache()

	protected function clearCache()
	{
		// clear posts cache if comment is visible
		if (isset($this->app->memcache)) {
			if (!$this->comment->spam &&
				$this->comment->status === SiteComment::STATUS_PUBLISHED) {
				$this->app->memcache->flushNs('photos');
			}
		}
	}

	// }}}
}

?>
