<?php

require_once 'Admin/pages/AdminPage.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatString.php';
require_once 'Pinhole/dataobjects/PinholeCommentWrapper.php';
require_once 'Pinhole/dataobjects/PinholePhotographerWrapper.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Pinhole/admin/PinholeCommentDisplay.php';

/**
 * Page to manage pending comments on photos
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeCommentIndex extends AdminPage
{
	// {{{ class constants

	const SHOW_UNAPPROVED = 1;
	const SHOW_ALL        = 2;
	const SHOW_ALL_SPAM   = 3;
	const SHOW_SPAM       = 4;

	// }}}
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Pinhole/admin/components/Comment/index.xml';

	/**
	 * @var string
	 */
	protected $where_clause;

	/**
	 * @var array
	 */
	protected $comments;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		$visibility_options = array(
			self::SHOW_UNAPPROVED => Pinhole::_('Pending Comments'),
			self::SHOW_ALL        => Pinhole::_('All Comments'),
			self::SHOW_ALL_SPAM   => Pinhole::_('All Comments, Including Spam'),
			self::SHOW_SPAM       => Pinhole::_('Spam Only'),
		);

		$visibility = $this->ui->getWidget('search_visibility');
		$visibility->addOptionsByArray($visibility_options);

		// if default comment status is moderated, only show pending comments
		// by default.
		if ($this->app->config->pinhole->default_comment_status === 'moderated') {
			$visibility->value = self::SHOW_UNAPPROVED;
		} else {
			$visibility->value = self::SHOW_ALL;
		}

		$this->processSearchUi();

		$this->initComments();
		$this->initCommentReplicator();
	}

	// }}}
	// {{{ protected function initComments()

	protected function initComments()
	{
		$sql = 'select count(1) from PinholeComment
			left outer join PinholePhotographer on
				PinholeComment.photographer = PinholePhotographer.id
			where '.$this->getWhereClause();

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		// load comments
		$sql = sprintf(
			'select PinholeComment.* from PinholeComment
			left outer join PinholePhotographer on
				PinholeComment.photographer = PinholePhotographer.id
			where %s
			order by createdate desc',
			$this->getWhereClause());

		$this->app->db->setLimit($pager->page_size, $pager->current_record);

		$wrapper = SwatDBClassMap::get('PinholeCommentWrapper');
		$comments = SwatDB::query($this->app->db, $sql, $wrapper);

		// init result message
		$visibility = $this->ui->getWidget('search_visibility')->value;
		switch ($visibility) {
		default:
		case self::SHOW_UNAPPROVED :
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage(
					Pinhole::_('pending comment'),
					Pinhole::_('pending comments'));

			break;
		case self::SHOW_ALL :
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage(
					Pinhole::_('comment'),
					Pinhole::_('comments'));

			break;

		case self::SHOW_ALL_SPAM :
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage(
					Pinhole::_('comment (including spam)'),
					Pinhole::_('comments (including spam)'));

			break;

		case self::SHOW_SPAM :
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage(
					Pinhole::_('spam comment'),
					Pinhole::_('spam comments'));

			break;
		}

		// efficiently load photos for all comments
		$instance_id = $this->app->getInstanceId();
		$photo_sql = sprintf('select PinholePhoto.id,
				PinholePhoto.title, PinholePhoto.original_filename
			from PinholePhoto
			inner join ImageSet on ImageSet.id = PinholePhoto.image_set
			where instance %s %s and PinholePhoto.id in (%%s)',
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

		$this->comments = array();
		foreach ($comments as $comment) {
			$this->comments[$comment->id] = $comment;
		}
	}

	// }}}
	// {{{ protected function initCommentReplicator()

	protected function initCommentReplicator()
	{
		$comment_display = $this->ui->getWidget('comment');
		$comment_display->setApplication($this->app);

		$replicator = $this->ui->getWidget('comment_replicator');
		$replicator->replication_ids = array_keys($this->comments);
	}

	// }}}
	// {{{ protected function processSearchUi()

	protected function processSearchUi()
	{
		$search_frame = $this->ui->getWidget('search_frame');
		$search_frame->init();
		$search_frame->process();

		$form = $this->ui->getWidget('search_form');
		if ($form->isProcessed()) {
			$this->saveState();
		}

		if ($this->hasState()) {
			$this->loadState();
		}

		$this->ui->getWidget('pager')->init();
		$this->ui->getWidget('pager')->process();
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		if ($this->where_clause === null) {
			$instance_id = $this->app->getInstanceId();

			$where = sprintf(
				'photo in (select PinholePhoto.id from PinholePhoto
					inner join ImageSet on ImageSet.id = PinholePhoto.image_set
					where instance %s %s)',
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'));

			$keywords = $this->ui->getWidget('search_keywords')->value;
			if (trim($keywords) != '') {
				$clause = new AdminSearchClause('bodytext', $keywords);
				$clause->table = 'PinholeComment';
				$clause->operator = AdminSearchClause::OP_CONTAINS;
				$where.= $clause->getClause($this->app->db, 'and');
			}

			$photographer = $this->ui->getWidget('search_photographer')->value;
			if (trim($photographer) != '') {
				$fullname_clause = new AdminSearchClause('fullname', $photographer);
				$fullname_clause->table = 'PinholeComment';
				$fullname_clause->operator = AdminSearchClause::OP_CONTAINS;

				$photographer_clause = new AdminSearchClause('fullname', $photographer);
				$photographer_clause->table = 'PinholePhotographer';
				$photographer_clause->operator = AdminSearchClause::OP_CONTAINS;

				$where.= ' and (';
				$where.= $fullname_clause->getClause($this->app->db, '');
				$where.= $photographer_clause->getClause($this->app->db, 'or');
				$where.= ')';
			}

			$visibility = $this->ui->getWidget('search_visibility')->value;
			switch ($visibility) {
			default:
			case self::SHOW_UNAPPROVED :
				$where.= sprintf(
					' and status = %s and spam = %s',
					$this->app->db->quote(SiteComment::STATUS_PENDING,
						'integer'),
					$this->app->db->quote(false, 'boolean'));

				break;

			case self::SHOW_ALL :
				$where.= sprintf(' and spam = %s',
					$this->app->db->quote(false, 'boolean'));

				break;

			case self::SHOW_ALL_SPAM :
				// no extra where needed
				break;

			case self::SHOW_SPAM :
				$where.= sprintf(' and spam = %s',
					$this->app->db->quote(true, 'boolean'));

				break;
			}

			$this->where_clause = $where;
		}
		return $this->where_clause;
	}

	// }}}

	// process phase
	// {{{ protected function clearState()

	/**
	 * Clears a saved search state
	 */
	protected function clearState()
	{
		if ($this->hasState()) {
			unset($this->app->session->{$this->getKey()});
		}
	}

	// }}}
	// {{{ protected function saveState()

	protected function saveState()
	{
		$search_form = $this->ui->getWidget('search_form');
		$search_state = $search_form->getDescendantStates();
		$this->app->session->{$this->getKey()} = $search_state;
	}

	// }}}
	// {{{ protected function loadState()

	/**
	 * Loads a saved search state for this page
	 *
	 * @return boolean true if a saved state exists for this page and false if
	 *                  it does not.
	 *
	 * @see PinholePhotoComments::hasState()
	 */
	protected function loadState()
	{
		$return = false;

		$search_form = $this->ui->getWidget('search_form');

		if ($this->hasState()) {
			$search_form->setDescendantStates(
				$this->app->session->{$this->getKey()});

			$return = true;
		}

		return $return;
	}

	// }}}
	// {{{ protected function hasState()

	/**
	 * Checks if this search page has stored search information
	 *
	 * @return boolean true if this page has stored search information and
	 *                  false if it does not.
	 */
	protected function hasState()
	{
		return isset($this->app->session->{$this->getKey()});
	}

	// }}}
	// {{{ protected function getKey()

	protected function getKey()
	{
		return $this->source.'_search_state';
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->buildMessages();
		$this->buildSearchForm();
		$this->buildCommentReplicator();
	}

	// }}}
	// {{{ protected function buildCommentReplicator()

	protected function buildCommentReplicator()
	{
		$comment_replicator = $this->ui->getWidget('comment_replicator');
		foreach ($comment_replicator->replication_ids as $id) {
			$comment_display = $comment_replicator->getWidget('comment', $id);
			$comment_display->setComment($this->comments[$id]);
		}
	}

	// }}}
	// {{{ protected function buildSearchForm()

	protected function buildSearchForm()
	{
		$form = $this->ui->getWidget('search_form', true);
		$form->action = $this->source;
	}

	// }}}
}

?>
