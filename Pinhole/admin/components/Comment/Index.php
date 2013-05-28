<?php

require_once 'Site/admin/components/Comment/Index.php';
require_once 'Pinhole/admin/PinholeCommentDisplay.php';
require_once 'Pinhole/dataobjects/PinholePhotographerWrapper.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Pinhole/dataobjects/PinholeCommentWrapper.php';

/**
 * Page to manage pending comments on photos
 *
 * @package   Pinhole
 * @copyright 2008-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeCommentIndex extends SiteCommentIndex
{
	// init phase
	// {{{ protected function getTable()

	protected function getTable()
	{
		return 'PinholeComment';
	}

	// }}}
	// {{{ protected function getCommentDisplayWidget()

	protected function getCommentDisplayWidget()
	{
		return new PinholeCommentDisplay('comment');
	}

	// }}}
	// {{{ protected function getCommentCount()

	protected function getCommentCount()
	{
		$sql = 'select count(1) from PinholeComment
			left outer join PinholePhotographer on PinholeComment.photographer = PinholePhotographer.id
			where '.$this->getWhereClause();

		return SwatDB::queryOne($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function getComments()

	protected function getComments($limit = null, $offset = null)
	{
		$sql = sprintf(
			'select PinholeComment.* from PinholeComment
			left outer join PinholePhotographer on PinholeComment.photographer = PinholePhotographer.id
			where %s
			order by createdate desc',
			$this->getWhereClause());

		$this->app->db->setLimit($limit, $offset);

		$wrapper = SwatDBClassMap::get('PinholeCommentWrapper');
		$comments = SwatDB::query($this->app->db, $sql, $wrapper);

		// efficiently load photos for all comments
		$instance_id = $this->app->getInstanceId();
		$photo_sql = sprintf('select PinholePhoto.*	from PinholePhoto
			inner join ImageSet on ImageSet.id = PinholePhoto.image_set
			where ImageSet.instance %s %s and PinholePhoto.id in (%%s)',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$photo_wrapper = SwatDBClassMap::get('PinholePhotoWrapper');
		$comments->loadAllSubDataObjects('photo', $this->app->db, $photo_sql,
			$photo_wrapper);

		// efficiently load photographers for all comments
		$instance_id = $this->app->getInstanceId();
		$photographer_sql = sprintf('select id, fullname
			from PinholePhotographer
			where instance %s %s and id in (%%s)',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$photographer_wrapper = SwatDBClassMap::get('PinholePhotographerWrapper');
		$comments->loadAllSubDataObjects('photographer', $this->app->db, $photographer_sql,
			$photographer_wrapper);

		return $comments;
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		if ($this->where_clause === null) {
			$where = parent::getWhereClause();

			$instance_id = $this->app->getInstanceId();

			$where.= sprintf(
				' and photo in (select PinholePhoto.id from PinholePhoto
					inner join ImageSet on ImageSet.id = PinholePhoto.image_set
					where instance %s %s)',
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'));

			$this->where_clause = $where;
		}
		return $this->where_clause;
	}

	// }}}
	// {{{ protected function getAuthorWhereClause()

	protected function getAuthorWhereClause()
	{
		$where = '';

		$author = $this->ui->getWidget('search_author')->value;
		if (trim($author) != '') {
			$fullname_clause = new AdminSearchClause('fullname', $author);
			$fullname_clause->table = 'PinholeComment';
			$fullname_clause->operator = AdminSearchClause::OP_CONTAINS;

			$photographer_clause = new AdminSearchClause('fullname', $author);
			$photographer_clause->table = 'PinholePhotographer';
			$photographer_clause->operator = AdminSearchClause::OP_CONTAINS;

			$where.= ' and (';
			$where.= $fullname_clause->getClause($this->app->db, '');
			$where.= $photographer_clause->getClause($this->app->db, 'or');
			$where.= ')';
		}

		return $where;
	}

	// }}}
	// {{{ protected function getDefaultVisibilityValue()

	protected function getDefaultVisibilityValue()
	{
		$value = parent::getDefaultVisibilityValue();

		// if default comment status is moderated, only show pending comments
		// by default.
		if ($this->app->config->pinhole->default_comment_status === 'moderated') {
			$value = self::SHOW_UNAPPROVED;
		}

		return $value;
	}

	// }}}
}

?>
