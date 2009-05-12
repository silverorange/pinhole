<?php

require_once 'Site/gadgets/SiteGadget.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'SwatI18N/SwatI18NLocale.php';

/**
 * Display the last X comments
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeCommentsGadget extends SiteGadget
{
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$comments = $this->getComments();

		if (count($comments) == 0) {
			echo Pinhole::_('No comments have been made.');
		} else {
			$locale = SwatI18NLocale::get();

			echo '<ul>';

			foreach ($comments as $comment) {
				echo '<li>';

				$date = new SwatDate($comment->createdate);
				$date->convertTZById($this->app->config->date->time_zone);
				$date_diff = $date->getHumanReadableDateDiff();

				$author = ($comment->photographer === null) ?
					$comment->fullname : $comment->photographer->fullname;

				$a_tag = new SwatHtmlTag('a');
				$a_tag->href = sprintf('photo/%s#comment%s',
					$comment->getInternalValue('photo'),
					$comment->id);

				$a_tag->setContent(sprintf('%s ago by %s',
					$date_diff, $author));

				$a_tag->display();

				$div_tag = new SwatHtmlTag('div');
				$div_tag->setContent(SwatString::condense(
					SwatString::ellipsizeRight($comment->bodytext, 100)));

				$div_tag->display();

				echo '</li>';
			}

			echo '</ul>';
		}
	}

	// }}}
	// {{{ protected function getPhotoStats()

	protected function getComments()
	{
		$limit = $this->getValue('limit');

		$cache_key = 'PinholeCommentsGadget.getComments.'.$limit;
		$value = $this->app->getCacheValue($cache_key, 'photos');
		if ($value !== false)
			return $value;

		$sql = "select PinholeComment.*
			from PinholeComment
			inner join PinholePhoto on PinholeComment.photo = PinholePhoto.id
			inner join ImageSet on ImageSet.id = PinholePhoto.image_set
			where ImageSet.instance %s %s and PinholePhoto.status = %s
			%s order by id desc";

		if (!$this->app->session->isLoggedIn()) {
			$private_where_clause = sprintf('and PinholePhoto.private = %s',
				$this->app->db->quote(false, 'boolean'));
		} else {
			$private_where_clause = '';
		}

		$sql = sprintf($sql,
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'),
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer'),
			$private_where_clause);

		$this->app->db->setLimit($limit);

		$comments = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('PinholeCommentWrapper'));

		$this->app->addCacheValue($comments, $cache_key, 'photos');

		return $comments;
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Pinhole::_('Comments'));
		$this->defineSetting('limit', Pinhole::_('Limit'), 'integer', 5);
		$this->defineDescription(Pinhole::_(
			'Displays recent comments.'));
	}

	// }}}
}

?>
