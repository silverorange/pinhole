<?php

require_once 'Site/pages/SiteXMLRPCServer.php';
require_once 'Site/layouts/SiteXMLRPCServerLayout.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Pinhole/dataobjects/PinholeComment.php';
require_once 'Services/Akismet.php';

/**
 * Performs actions on comments via AJAX
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeCommentAjaxServer extends SiteXMLRPCServer
{
	// {{{ public function spam()

	/**
	 * Marks a comment as spam
	 *
	 * @param integer $comment_id the id of the comment to mark as spam.
	 *
	 * @return boolean true.
	 */
	public function spam($comment_id)
	{
		$comment_class = SwatDBClassMap::get('PinholeComment');
		$comment = new $comment_class();
		$comment->setDatabase($this->app->db);
		if ($comment->load($comment_id, $this->app->getInstance())) {
			if (!$comment->spam) {

				// submit spam to akismet
				if ($this->app->config->pinhole->akismet_key !== null) {
					$uri = $this->app->getFrontendBaseHref().
						$this->app->config->pinhole->path;

					$date = clone $comment->post->publish_date;
					$date->convertTZ($this->app->default_time_zone);
					$permalink = sprintf('%sphoto/%s',
						$uri, $comment->photo->id);

					try {
						$akismet = new Services_Akismet($uri,
							$this->app->config->pinhole->akismet_key);

						$akismet_comment = new Services_Akismet_Comment();
						$akismet_comment->setAuthor($comment->fullname);
						$akismet_comment->setAuthorEmail($comment->email);
						$akismet_comment->setAuthorUri($comment->link);
						$akismet_comment->setContent($comment->bodytext);
						$akismet_comment->setPostPermalink($permalink);

						$akismet->submitSpam($akismet_comment);
					} catch (Exception $e) {
					}
				}

				$comment->spam = true;
				$comment->save();

				if (isset($this->app->memcache)) {
					$this->app->memcache->flushNS('photos');
				}
			}
		}

		return true;
	}

	// }}}
	// {{{ public function notSpam()

	/**
	 * Marks a comment as not spam
	 *
	 * @param integer $comment_id the id of the comment to mark as not spam.
	 *
	 * @return boolean true.
	 */
	public function notSpam($comment_id)
	{
		$comment_class = SwatDBClassMap::get('PinholeComment');
		$comment = new $comment_class();
		$comment->setDatabase($this->app->db);
		if ($comment->load($comment_id, $this->app->getInstance())) {
			if ($comment->spam) {

				// submit false positive to akismet
				if ($this->app->config->pinhole->akismet_key !== null) {
					$uri = $this->app->getFrontendBaseHref().
						$this->app->config->pinhole->path;

					$date = clone $comment->post->publish_date;
					$date->convertTZ($this->app->default_time_zone);
					$permalink = sprintf('%sphoto/%s',
						$uri, $comment->photo->id);

					try {
						$akismet = new Services_Akismet($uri,
							$this->app->config->pinhole->akismet_key);

						$akismet_comment = new Services_Akismet_Comment();
						$akismet_comment->setAuthor($comment->fullname);
						$akismet_comment->setAuthorEmail($comment->email);
						$akismet_comment->setAuthorUri($comment->link);
						$akismet_comment->setContent($comment->bodytext);
						$akismet_comment->setPostPermalink($permalink);

						$akismet->submitFalsePositive($akismet_comment);
					} catch (Exception $e) {
					}
				}

				$comment->spam = false;
				$comment->save();

				if (isset($this->app->memcache)) {
					$this->app->memcache->flushNS('photos');
				}
			}
		}

		return true;
	}

	// }}}
	// {{{ public function publish()

	/**
	 * Publishes a comment
	 *
	 * @param integer $comment_id the id of the comment to publish.
	 *
	 * @return boolean true.
	 */
	public function publish($comment_id)
	{
		$class_name = SwatDBClassMap::get('PinholeComment');
		$comment = new $class_name();
		$comment->setDatabase($this->app->db);
		if ($comment->load($comment_id, $this->app->getInstance())) {
			if ($comment->status !== SiteComment::STATUS_PUBLISHED) {
				$comment->status = SiteComment::STATUS_PUBLISHED;
				$comment->save();

				if (isset($this->app->memcache)) {
					$this->app->memcache->flushNS('photos');
				}
			}
		}

		return true;
	}

	// }}}
	// {{{ public function unpublish()

	/**
	 * Unpublishes a comment
	 *
	 * @param integer $comment_id the id of the comment to unpublish.
	 *
	 * @return boolean true.
	 */
	public function unpublish($comment_id)
	{
		$class_name = SwatDBClassMap::get('PinholeComment');
		$comment = new $class_name();
		$comment->setDatabase($this->app->db);
		if ($comment->load($comment_id, $this->app->getInstance())) {
			if ($comment->status !== SiteComment::STATUS_UNPUBLISHED) {
				$comment->status = SiteComment::STATUS_UNPUBLISHED;
				$comment->save();

				if (isset($this->app->memcache)) {
					$this->app->memcache->flushNS('photos');
				}
			}
		}

		return true;
	}

	// }}}
	// {{{ public function delete()

	/**
	 * Deletes a comment
	 *
	 * @param integer $comment_id the id of the comment to delete.
	 *
	 * @return boolean true.
	 */
	public function delete($comment_id)
	{
		$class_name = SwatDBClassMap::get('PinholeComment');
		$comment = new $class_name();
		$comment->setDatabase($this->app->db);
		if ($comment->load($comment_id, $this->app->getInstance())) {
			$comment->delete();

			if (isset($this->app->memcache)) {
				$this->app->memcache->flushNS('photos');
			}
		}

		return true;
	}

	// }}}
}

?>