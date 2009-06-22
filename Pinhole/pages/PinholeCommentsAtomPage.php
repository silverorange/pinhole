<?php

require_once 'Site/SiteCommentStatus.php';
require_once 'Site/pages/SitePage.php';
require_once 'Pinhole/dataobjects/PinholePhotographerWrapper.php';
require_once 'Pinhole/dataobjects/PinholeCommentWrapper.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'XML/Atom/Entry.php';

/**
 * Displays an Atom feed of all recent comments in reverse chronological order
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeCommentsAtomPage extends SitePage
{
	// {{{ protected properties

	/**
	 * @var PinholeCommentWrapper
	 */
	protected $comments;

	/**
	 * The total number of comments for this feed.
	 *
	 * @var integer
	 */
	protected $total_count;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteWebApplication $app, SiteLayout $layout,
		array $arguments = array())
	{
		$layout = new SiteLayout($app, 'Site/layouts/xhtml/atom.php');

		parent::__construct($app, $layout, $arguments);
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'page' => array(0, 1),
		);
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();
		$this->initComments($this->getArgument('page'));
	}

	// }}}
	// {{{ protected function initComments()

	protected function initComments($page)
	{
		// get comments for this page
		$this->comments = false;

		$key = $this->getCommentsCacheKey();
		$this->comments = $this->app->getCacheRecordset('photos',
			SwatDBClassMap::get('PinholeCommentWrapper'), $key);

		if ($this->comments === false) {
			$sql = sprintf('select PinholeComment.* from
				PinholeComment %s where %s
				order by PinholeComment.createdate desc',
				$this->getJoinClause(),
				$this->getWhereClause());

			$offset = ($page - 1) * $this->getPageSize();
			$this->app->db->setLimit($this->getPageSize(), $offset);

			$wrapper = SwatDBClassMap::get('PinholeCommentWrapper');
			$this->comments = SwatDB::query($this->app->db, $sql, $wrapper);

			// efficiently load photos
			$photo_wrapper = SwatDBClassMap::get('PinholePhotoWrapper');
			$photo_sql = 'select * from PinholePhoto where id in (%s)';

			$this->comments->loadAllSubDataObjects('photo', $this->app->db,
				$photo_sql, $photo_wrapper);

			// efficiently load photographers
			$photographer_wrapper = SwatDBClassMap::get('PinholePhotographerWrapper');
			$photographer_sql = 'select id, fullname, shortname, email, visible
				from PinholePhotographer
				where id in (%s)';

			$this->comments->loadAllSubDataObjects('photographer', $this->app->db,
				$photographer_sql, $photographer_wrapper);

			$this->app->addCacheRecordset($this->comments, $key, 'photos');
		} else {
			$this->comments->setDatabase($this->app->db);
		}

		// if we're not on the first page and there are no comments, 404
		if ($page > 1 && count($this->comments) === 0) {
			throw new SiteNotFoundException('Page not found.');
		}

		// get total number of comments
		$this->total_count = false;

		$total_key = $this->getTotalCountCacheKey();
		$this->total_count = $this->app->getCacheValue('photos', $total_key);

		if ($this->total_count === false) {
			$sql = sprintf('select count(1) from PinholeComment %s where %s',
				$this->getJoinClause(),
				$this->getWhereClause());

			$this->total_count = SwatDB::queryOne($this->app->db, $sql);
			$this->app->addCacheValue($this->total_count, 'photos', $total_key);
		}
	}

	// }}}
	// {{{ protected function getJoinClause()

	protected function getJoinClause()
	{
		$instance_id = $this->app->getInstanceId();
		return sprintf('inner join PinholePhoto on
				PinholeComment.photo = PinholePhoto.id and
				PinholePhoto.status = %s and PinholePhoto.private = %s
				and PinholePhoto.comment_status != %s
				inner join ImageSet on ImageSet.id = PinholePhoto.image_set
					and ImageSet.instance %s %s',
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer'),
			$this->app->db->quote(false, 'boolean'),
			$this->app->db->quote(SiteCommentStatus::CLOSED, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		return sprintf('PinholeComment.status = %s
			and PinholeComment.spam = %s',
			$this->app->db->quote(SiteComment::STATUS_PUBLISHED, 'integer'),
			$this->app->db->quote(false, 'boolean'));
	}

	// }}}
	// {{{ protected function getCommentsCacheKey()

	protected function getCommentsCacheKey()
	{
		return 'comments_feed_page'.$this->getArgument('page');
	}

	// }}}
	// {{{ protected function getTotalCountCacheKey()

	protected function getTotalCountCacheKey()
	{
		return 'comments_feed_total_count';
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		$this->buildAtomFeed();
		$this->buildPagination($this->feed);

		$this->layout->startCapture('content');
		$this->displayAtomFeed();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildAtomFeed()

	protected function buildAtomFeed()
	{
		$site_base_href  = $this->app->getBaseHref();

		$this->feed = new XML_Atom_Feed($this->getPinholeBaseHref(),
			$this->app->config->site->title);

		$this->feed->setSubTitle(Pinhole::_('Recent Comments'));
		$this->feed->addLink($site_base_href.$this->source, 'self',
			'application/atom+xml');

		$this->feed->setGenerator('Pinhole');
		$this->feed->setBase($site_base_href);

		//$author_uri = '';
		//$this->feed->addAuthor($this->post->author->name, $author_uri,
		//	$this->post->author->email);

		foreach ($this->comments as $comment)
			$this->feed->addEntry($this->getEntry($comment));
	}

	// }}}
	// {{{ protected function buildPagination()

	protected function buildPagination(XML_Atom_Feed $feed)
	{
		$page = $this->getArgument('page');
		$type = 'application/atom+xml';
		$last = intval(ceil($this->getTotalCount() / $this->getPageSize()));

		$base_href = $this->getPinholeBaseHref().'feed/comments';

		$feed->addLink($base_href, 'first', $type);
		$feed->addLink($base_href.'/page'.$last, 'last', $type);

		if ($page > 1) {
			$feed->addLink($base_href.'/page'.($page - 1),
				'previous', $type);
		}

		if ($page < $last) {
			$feed->addLink($base_href.'/page'.($page + 1),
				'next', $type);
		}
	}

	// }}}
	// {{{ protected function getEntry()

	protected function getEntry(PinholeComment $comment)
	{
		$photo_uri = $this->getPinholeBaseHref();
		$photo_uri.= 'photo/'.$comment->photo->id;

		$comment_uri = $photo_uri.'#comment'.$comment->id;

		if ($comment->photographer !== null) {
			$author_name = $comment->photographer->fullname;
			$author_uri  = '';
			$author_email = '';
		} else {
			$author_name  = $comment->fullname;
			$author_uri   = $comment->link;
			$author_email = '';
		}

		$entry = new XML_Atom_Entry($comment_uri,
			sprintf(Pinhole::_('%s on “%s”'), $author_name,
				$comment->photo->getTitle()),
			$comment->createdate);

		ob_start();

		$img_tag = $comment->photo->getImgTag('thumb');

		$a_tag = new SwatHtmlTag('a');
		$a_tag->href = $this->getPinholeBaseHref().
			'photo/'.$comment->photo->id;

		$a_tag->style = sprintf('display: block; position: absolute; '.
			'width: %dpx;',
			$img_tag->width);

		$a_tag->open();
		echo $img_tag;
		$a_tag->close();

		printf('<div style="margin-left: %dpx;">', $img_tag->width + 20);
		echo SiteCommentFilter::toXhtml($comment->bodytext);
		echo '</div>';

		$entry->setContent(ob_get_clean(), 'html');
		$entry->addAuthor($author_name, $author_uri, $author_email);
		$entry->addLink($comment_uri, 'alternate', 'text/html');

		return $entry;
	}

	// }}}
	// {{{ protected function displayAtomFeed()

	protected function displayAtomFeed()
	{
		echo $this->feed;
	}

	// }}}

	// helper methods
	// {{{ protected function getPinholeBaseHref()

	protected function getPinholeBaseHref()
	{
		$site_base_href  = $this->app->getBaseHref();
		return $site_base_href.$this->app->config->pinhole->path;
	}

	// }}}
	// {{{ protected function getTotalCount()

	protected function getTotalCount()
	{
		return $this->total_count;
	}

	// }}}
	// {{{ protected function getPageSize()

	protected function getPageSize()
	{
		return 50;
	}

	// }}}
}

?>
