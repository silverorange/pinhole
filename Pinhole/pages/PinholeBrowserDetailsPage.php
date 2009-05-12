<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatDetailsViewField.php';
require_once 'Swat/SwatTextCellRenderer.php';
require_once 'Swat/SwatLinkCellRenderer.php';
require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatString.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/pages/PinholeBrowserPage.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';

// comments
require_once 'Pinhole/dataobjects/PinholeComment.php';
require_once 'Services/Akismet.php';
require_once 'NateGoSearch/NateGoSearch.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeBrowserDetailsPage extends PinholeBrowserPage
{
	// {{{ class constants

	const THANK_YOU_ID = 'thank-you';

	// }}}
	// {{{ protected properties

	/**
	 * @var PinholePhoto
	 */
	protected $photo;

	/**
	 * @var string
	 */
	protected $default_dimension = 'large';

	/**
	 * @var PinholeImageDimension
	 */
	protected $dimension;

	/**
	 * @var PinholeComment
	 */
	protected $comment;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout,
		array $arguments)
	{
		$app->memcache->flush();

		parent::__construct($app, $layout, $arguments);

		$this->ui_xml = 'Pinhole/pages/browser-details.xml';

		$this->createPhoto($this->getArgument('photo_id'));
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'photo_id' => array(0, null),
			'dimension_shortname' => array(1, null),
		);
	}

	// }}}
	// {{{ protected function createPhoto()

	protected function createPhoto($photo_id)
	{
		$cache_key = 'PinholePhoto.'.$photo_id;
		$photo = $this->app->getCacheValue($cache_key, 'photos');
		if ($photo !== false) {
			$this->photo = $photo;
			$this->photo->setDatabase($this->app->db);
		}

		if ($this->photo === null) {
			$photo_class = SwatDBClassMap::get('PinholePhoto');
			$this->photo = new $photo_class();
			$this->photo->setDatabase($this->app->db);
			$this->photo->load($photo_id);
			$this->app->addCacheValue($this->photo, $cache_key, 'photos');
		}

		if ($this->photo !== null && $this->photo->id !== null) {
			// ensure we are loading a photo in the current site instance
			$current_instance_id = $this->app->getInstanceId();

			if ($current_instance_id === null)
				$photo_instance_id = $this->photo->image_set->instance;
			else
				$photo_instance_id = $this->photo->image_set->instance->id;

			if ($photo_instance_id != $current_instance_id) {
				throw new SiteNotFoundException(sprintf(
					'Photo does not belong to the current instance: %s.',
					$this->app->getInstance()->shortname));
			}
		} else {
			// photo was not found
			throw new SiteNotFoundException(sprintf(
				'No photo with the id %d exists.', $photo_id));
		}
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->dimension = $this->initDimension(
			$this->getArgument('dimension_shortname'));
	}

	// }}}
	// {{{ protected function initDimension()

	protected function initDimension($shortname = null)
	{
		if ($shortname === null) {
			if (isset($this->app->cookie->display_dimension)) {
				$shortname = $this->app->cookie->display_dimension;
			} else
				$shortname = $this->default_dimension;
		}

		$class_name = SwatDBClassMap::get('PinholeImageDimension');
		$display_dimension = new $class_name();
		$display_dimension->setDatabase($this->app->db);
		$found = $display_dimension->loadByShortname('photos', $shortname);

		if ($found && !$display_dimension->selectable)
			throw new SiteNotFoundException(sprintf('Dimension “%s” is not '.
				'a selectable photo dimension', $shortname));

		$dimension = $this->photo->getClosestSelectableDimensionTo($shortname);

		$this->app->cookie->setCookie('display_dimension',
			$dimension->shortname, strtotime('+1 year'), '/',
			$this->app->getBaseHref());

		return $dimension;
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();
		$this->processCommentUi();

		if ($this->photo->private && !$this->app->session->isLoggedIn()) {
			$this->app->replacePage('login');

			$referer = 'photo/'.$this->photo->id;

			if ($this->getArgument('dimension_shortname') !== null)
				$referer.= '/'.$this->getArgument('dimension_shortname');

			if (count($this->tag_list))
				$referer.= '?'.(string)$this->tag_list;

			$this->app->getPage()->setReferrer($referer);
		}
	}

	// }}}
	// {{{ protected function processCommentUi()

	protected function processCommentUi()
	{
		$form = $this->ui->getWidget('comment_edit_form');

		// wrap form processing in try/catch to catch bad input from spambots
		try {
			$form->process();
		} catch (SwatInvalidSerializedDataException $e) {
			$this->app->replacePage('httperror');
			$this->app->getPage()->setStatus(400);
			return;
		}

		$comment_status = $this->photo->comment_status;
		if (($comment_status == PinholePhoto::COMMENT_STATUS_OPEN ||
			$comment_status == PinholePhoto::COMMENT_STATUS_MODERATED) &&
			$form->isProcessed() && !$form->hasMessage()) {

			$this->processComment();

			if ($this->ui->getWidget('post_button')->hasBeenClicked()) {
				$uri = 'photo/'.$this->photo->id;
				$get_vars = array();
				$target = null;

				if (count($this->tag_list) > 0)
					$get_vars[] = $this->tag_list->__toString();

				$this->saveComment();

				switch ($this->photo->comment_status) {
				case PinholePhoto::COMMENT_STATUS_OPEN:
					if (count($get_vars) == 0)
						$get_vars[] = '';

					$get_vars[] = self::THANK_YOU_ID;
					$target = 'comment'.$this->comment->id;
					break;

				case PinholePhoto::COMMENT_STATUS_MODERATED:
					if (count($get_vars) == 0)
						$get_vars[] = '';

					$get_vars[] = self::THANK_YOU_ID;
					$target = 'submit_comment';
					break;
				}

				if (count($get_vars) > 0)
					$uri.= '?'.implode('&', $get_vars);

				if ($target !== null)
					$uri.= '#'.$target;

				$this->app->relocate($uri);
			}
		}
	}

	// }}}
	// {{{ protected function processComment()

	protected function processComment()
	{
		$now = new SwatDate();
		$now->toUTC();

		$fullname   = $this->ui->getWidget('fullname');
		$link       = $this->ui->getWidget('link');
		$email      = $this->ui->getWidget('email');
		$bodytext   = $this->ui->getWidget('bodytext');

		if (isset($_SERVER['REMOTE_ADDR'])) {
			$ip_address = substr($_SERVER['REMOTE_ADDR'], 0, 15);
		} else {
			$ip_address = null;
		}

		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$user_agent = substr($_SERVER['HTTP_USER_AGENT'], 0, 255);
		} else {
			$user_agent = null;
		}

		$class_name = SwatDBClassMap::get('PinholeComment');
		$this->comment = new $class_name();

		$this->comment->fullname   = $fullname->value;
		$this->comment->link       = $link->value;
		$this->comment->email      = $email->value;
		$this->comment->bodytext   = $bodytext->value;
		$this->comment->createdate = $now;
		$this->comment->ip_address = $ip_address;
		$this->comment->user_agent = $user_agent;

		switch ($this->photo->comment_status) {
		case PinholePhoto::COMMENT_STATUS_OPEN:
			$this->comment->status = SiteComment::STATUS_PUBLISHED;
			break;

		case PinholePhoto::COMMENT_STATUS_MODERATED:
			$this->comment->status = SiteComment::STATUS_PENDING;
			break;
		}

		$this->comment->photo = $this->photo;
	}

	// }}}
	// {{{ protected function saveComment()

	protected function saveComment()
	{
		if ($this->ui->getWidget('remember_me')->value) {
			$this->saveCommentCookie();
		} else {
			$this->deleteCommentCookie();
		}

		$this->comment->spam = $this->isCommentSpam($this->comment);

		$this->photo->comments->add($this->comment);
		$this->photo->save();
		$this->addToSearchQueue();

		// clear photos cache if comment is visible
		if (isset($this->app->memcache) && !$this->comment->spam &&
			$this->comment->status === SiteComment::STATUS_PUBLISHED) {
			$this->app->memcache->flushNs('photos');
		}
	}

	// }}}
	// {{{ protected function saveCommentCookie()

	protected function saveCommentCookie()
	{
		$fullname = $this->ui->getWidget('fullname')->value;
		$link     = $this->ui->getWidget('link')->value;
		$email    = $this->ui->getWidget('email')->value;

		$value = array(
			'fullname' => $fullname,
			'link'     => $link,
			'email'    => $email,
		);

		$this->app->cookie->setCookie('comment_credentials', $value);
	}

	// }}}
	// {{{ protected function deleteCommentCookie()

	protected function deleteCommentCookie()
	{
		$this->app->cookie->removeCookie('comment_credentials');
	}

	// }}}
	// {{{ protected function isCommentSpam()

	protected function isCommentSpam(PinholeComment $comment)
	{
		$is_spam = false;

		if ($this->app->config->pinhole->akismet_key !== null) {
			$uri = $this->app->getBaseHref().$this->app->config->pinhole->path;
			$permalink = sprintf('%sphoto/%s', $uri, $this->photo->id);

			try {
				$akismet = new Services_Akismet($uri,
					$this->app->config->pinhole->akismet_key);

				$akismet_comment = new Services_Akismet_Comment();
				$akismet_comment->setAuthor($comment->fullname);
				$akismet_comment->setAuthorEmail($comment->email);
				$akismet_comment->setAuthorUri($comment->link);
				$akismet_comment->setContent($comment->bodytext);
				$akismet_comment->setPostPermalink($permalink);

				$is_spam = $akismet->isSpam($akismet_comment);
			} catch (Exception $e) {
			}
		}

		return $is_spam;
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue()
	{
		$this->addPhotoToSearchQueue();
		$this->addCommentToSearchQueue();
	}

	// }}}
	// {{{ protected function addPhotoToSearchQueue()

	protected function addPhotoToSearchQueue()
	{
		$type = NateGoSearch::getDocumentType($this->app->db, 'photo');

		if ($type === null)
			return;

		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->photo->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->photo->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function addCommentToSearchQueue()

	protected function addCommentToSearchQueue()
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

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->buildDetailsView();
		$this->buildComments();
		$this->buildMetaData();
		$this->buildLayout();
		$this->buildPhotoNextPrev();

		$description = $this->ui->getWidget('description');
		// Set to text/xml for now pending review in ticket #1159.
		$description->content_type = 'text/xml';
		$description->content = $this->photo->description;

		$username = $this->app->config->clustershot->username;
		if ($this->photo->for_sale && $username !== null)
			$this->appendForSaleLink($description);
	}

	// }}}
	// {{{ protected function buildLayout()

	protected function buildLayout()
	{
		$title = $this->photo->getTitle();

		if (isset($this->layout->navbar)) {
			if ($title == '')
				$this->layout->navbar->createEntry(Pinhole::_('Photo'));
			else
				$this->layout->navbar->createEntry($title);
		}

		// Set YUI Grid CSS class for one full-width column on details page.
		$this->layout->data->yui_grid_class = 'yui-t7';

		// Set photo title.
		if ($title != '') {
			$this->layout->data->title = SwatString::minimizeEntities($title);

			$this->layout->data->html_title.= $title;
		}

		if (count($this->tag_list) > 0) {
			if ($title != '')
				$this->layout->data->html_title.= ' (';

			$this->layout->data->html_title.= $this->tag_list->getAsList();

			if ($title != '')
				$this->layout->data->html_title.= ')';
		}


		if ($this->photo->description != '')
			$this->layout->data->meta_description.=
				(($this->layout->data->meta_description == '') ? '' : ' ').
				strip_tags($this->photo->description);

		if (count($this->photo->tags) != 0) {
			$tags = array();
			foreach ($this->photo->tags as $tag)
				$tags[] = $tag->title;

			$this->layout->data->meta_keywords.=
				(($this->layout->data->meta_keywords == '') ? '' : ' ').
				implode(', ', $tags);
		}
	}

	// }}}
	// {{{ protected function buildMetaData()

	protected function buildMetaData()
	{
		$photo_meta_data = $this->photo->meta_data;

		$this->ui->getWidget('photo_details')->visible =
			(count($photo_meta_data) > 0);

		$view = $this->ui->getWidget('photo_details_view');

		foreach ($photo_meta_data as $meta_data) {
			$field = new SwatDetailsViewField();
			$field->title = $meta_data->title;

			if ($meta_data->machine_tag) {
				$renderer = new SwatLinkCellRenderer();
				$renderer->link = sprintf('%stag?%s',
					$this->app->config->pinhole->path,
					$meta_data->getURI());
			} else {
				$renderer = new SwatTextCellRenderer();
			}

			$renderer->text = $meta_data->value;

			$view->appendField($field);
			$field->addRenderer($renderer);
		}

	}

	// }}}
	// {{{ protected function buildPhotoNextPrev()

	protected function buildPhotoNextPrev()
	{
		$photo_next_prev = $this->ui->getWidget('photo_next_prev');
		$photo_next_prev->setPhoto($this->photo);
		$photo_next_prev->setTagList($this->tag_list);
		$photo_next_prev->base = $this->app->config->pinhole->path;
	}

	// }}}
	// {{{ protected function buildSubTagListView()

	protected function buildSubTagListView()
	{
		// don't show tag-list on this page
		return;
	}

	// }}}
	// {{{ protected function getPhotoDetailsStore()

	protected function getPhotoDetailsStore()
	{
		return new SwatDetailsStore($this->photo);
	}

	// }}}
	// {{{ protected function buildTagListView()

	protected function buildTagListView()
	{
		if (!$this->ui->hasWidget('tag_list_view'))
			return;

		parent::buildTagListView();

		$tag_list_view = $this->ui->getWidget('tag_list_view');
		$tag_list_view->rss_dimension_shortname =
			$this->dimension->shortname;
	}

	// }}}
	// {{{ protected function displayPhoto()

	protected function displayPhoto()
	{
		$width = $this->photo->getWidth($this->dimension->shortname);
		$height = $this->photo->getHeight($this->dimension->shortname);

		$panorama = ($width > $this->dimension->max_width ||
			$height > $this->dimension->max_height);

		$next_prev = $this->tag_list->getNextPrevPhotos($this->photo);
		if ($next_prev['next'] !== null) {
			$href = sprintf('%sphoto/%s', $this->app->config->pinhole->path,
				$next_prev['next']->id);

			if (count($this->tag_list) > 0)
				$href.= '?'.$this->tag_list->__toString();

			$a_tag = new SwatHtmlTag('a');
			$a_tag->href = $href;
			$a_tag->title = Pinhole::_('View next image');
			$a_tag->open();
		}

		$img_tag = $this->photo->getImgTag(
			$this->dimension->shortname);

		$img_tag->class = 'pinhole-photo pinhole-photo-primary';
		$img_tag->display();

		if ($next_prev['next'] !== null)
			$a_tag->close();
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$this->displayPhoto();
		parent::displayContent();
	}

	// }}}

	// build details view
	// {{{ protected function buildDetailsView()

	protected function buildDetailsView()
	{
		$view = $this->ui->getWidget('photo_date_view');
		$view->data = $this->getPhotoDetailsStore();

		$this->buildDimensions($view);
		$this->buildPhotoDate($view);
		$this->buildTags($view);
	}

	// }}}
	// {{{ protected function buildPhotoDate()

	protected function buildPhotoDate(SwatDetailsView $view)
	{
		$photo_date = $view->getField('photo_date');

		if ($this->photo->photo_date === null) {
			$photo_date->visible = false;
		} else {
			$date = new SwatDate($this->photo->photo_date);
			$date->convertTZByID($this->photo->photo_time_zone);

			$date_links = $photo_date->getRenderer('date_links');
			$date_links->content_type = 'text/xml';
			$date_links->text = sprintf(Pinhole::_('<div id="photo_links">
				View photos taken on the same: '.
				'<a href="tag?date.date=%1$s-%2$s-%3$s">day</a>, '.
				'<a href="tag?date.week=%1$s-%2$s-%3$s">week</a>, '.
				'<a href="tag?date.month=%2$s/date.year=%1$s">month</a>, '.
				'<a href="tag?date.year=%1$s">year</a>.</div>'),
				$date->format('%Y'),
				$date->format('%m'),
				$date->format('%d'));
		}
	}

	// }}}
	// {{{ protected function buildTags()

	protected function buildTags(SwatDetailsView $view)
	{
		$tag_array = array();
		foreach ($this->photo->tags as $tag) {
			$a_tag = new SwatHtmlTag('a');
			$a_tag->href = $this->app->config->pinhole->path.'tag?'.$tag->name;
			$a_tag->setContent($tag->title);
			$tag_array[] = (string) $a_tag;
		}

		if (count($tag_array) > 0) {
			$view->getField('tags')->getFirstRenderer()->text =
				implode(', ', $tag_array);
		} else {
			$view->getField('tags')->visible = false;
		}
	}

	// }}}
	// {{{ protected function buildDimensions()

	protected function buildDimensions(SwatDetailsView $view)
	{
		$dimensions = $this->getSelectableDimensions();

		if (count($dimensions) <= 1) {
			$view->getField('dimensions')->visible = false;
			return;
		}

		$list = array();

		foreach ($dimensions as $dimension) {
			ob_start();

			if ($dimension->id == $this->dimension->id) {
				$span_tag = new SwatHtmlTag('span');
				$span_tag->setContent($dimension->title);
				$span_tag->display();
			} else {
				$a_tag = new SwatHtmlTag('a');
				$a_tag->href = $this->app->config->pinhole->path;

				if ($dimension->shortname == 'original')
					$a_tag->href.= $this->photo->getUri('original');
				else
					$a_tag->href.= 'photo/'.$this->photo->id.'/'.
						$dimension->shortname;

				if (count($this->tag_list) > 0)
					$a_tag->href.= '?'.$this->tag_list->__toString();

				$a_tag->setContent($dimension->title);
				$a_tag->display();

				if ($dimension->shortname == 'original') {
					printf(' (%d × %d pixels)',
						$this->photo->getWidth($dimension->shortname),
						$this->photo->getHeight($dimension->shortname));
				}
			}

			$list[] = ob_get_clean();
		}

		$view->getField('dimensions')->getFirstRenderer()->text =
			implode(', ', $list);
	}

	// }}}
	// {{{ protected function getSelectableDimensions()

	protected function getSelectableDimensions()
	{
		if (isset($this->app->memcache)) {
			$cache_key = 'PinholeBrowserDetailsPage.getSelectableDimensions.'.
				$this->photo->id;

			$dimensions = $this->app->memcache->getNs('photos', $cache_key);
			if ($dimensions !== false)
				return $dimensions;
		}

		$dimensions = clone $this->photo->getSelectableDimensions();

		if (isset($this->app->memcache))
			$this->app->memcache->setNs('photos', $cache_key, $dimensions);

		return $dimensions;
	}

	// }}}
	// {{{ protected function appendForSaleLink()

	protected function appendForSaleLink($description)
	{
		$uri = $this->app->getBaseHref().'photo/'.$this->photo->id;
		$username = $this->app->config->clustershot->username;

		ob_start();
		// Link to relocate
		echo '<div id="cluster_shot_price_link">';

		$a_tag = new SwatHtmlTag('a');
		$a_tag->setContent(Pinhole::_('Buy this photo'));
		$a_tag->href = 'http://www.clustershot.com/'.$username.
			'/relocate?uri='.urlencode($uri);

		$a_tag->display();

		printf(Pinhole::_(' on %s'),
			'<a href="http://www.clustershot.com">ClusterShot.com</a>');

		echo '</div>';

		/*
		// Ajax lookup - doesn't work for now because of xss error
		echo '<script src="http://www.clustershot.com/'.
			'javascript/purchase-link.js" type="text/javascript">'.
			'</script>';

		Swat::displayInlineJavaScript(sprintf('
			ClusterShotLink(%s, %s);',
			SwatString::quoteJavaScriptString($username),
			SwatString::quoteJavaScriptString($uri)));
		*/

		$description->content.= ob_get_clean();
	}

	// }}}

	// display comments
	// {{{ protected function buildComments()

	protected function buildComments()
	{
		$global_status = $this->app->config->pinhole->global_comment_status;
		if ($global_status === null) {
			$status = $this->photo->comment_status;
		} elseif ($global_status == true) {
			// comments are globally turned on
			$status = $this->app->config->default_comment_status;
		} else {
			// comments are globally turned off
			$this->ui->getWidget('comment_frame')->visible = false;
			$this->ui->getWidget('comment_edit_frame')->visible = false;
			return;
		}

		if ($this->ui->getWidget('comment_edit_form')->hasMessage() ||
			$status == PinholePhoto::COMMENT_STATUS_MODERATED ||
			$status == PinholePhoto::COMMENT_STATUS_LOCKED ||
			$this->ui->getWidget('preview_button')->hasBeenClicked()) {
			$this->ui->getWidget('submit_comment')->visible = true;
		}

		if ($status != PinholePhoto::COMMENT_STATUS_CLOSED) {
			$comments = $this->photo->getVisibleComments();

			if (count($comments) > 0) {
				$this->ui->getWidget('comments_frame')->visible = true;

				ob_start();
				$div_tag = new SwatHtmlTag('div');
				$div_tag->id = 'comments';
				$div_tag->class = 'photo-comments';
				$div_tag->open();

				foreach ($comments as $comment) {
					$this->displayComment($comment);
				}

				$div_tag->close();
				$this->ui->getWidget('comments')->content = ob_get_clean();
			}
		}

		$this->buildCommentUi();
	}

	// }}}
	// {{{ protected function buildCommentUi()

	protected function buildCommentUi()
	{
		$ui              = $this->ui;
		$form            = $ui->getWidget('comment_edit_form');
		$show_thank_you  = array_key_exists(self::THANK_YOU_ID, $_GET);

		$uri = 'photo/'.$this->photo->id;
		if (count($this->tag_list) > 0)
			$uri.= '?'.$this->tag_list->__toString();

		switch ($this->photo->comment_status) {
		case PinholePhoto::COMMENT_STATUS_OPEN:
		case PinholePhoto::COMMENT_STATUS_MODERATED:
			$form->action = $uri.'#submit_comment';
			try {
				if (isset($this->app->cookie->comment_credentials)) {
					$values = $this->app->cookie->comment_credentials;
					$ui->getWidget('fullname')->value    = $values['fullname'];
					$ui->getWidget('link')->value        = $values['link'];
					$ui->getWidget('email')->value       = $values['email'];
					$ui->getWidget('remember_me')->value = true;
				}
			} catch (SiteCookieException $e) {
				// ignore cookie errors, but delete the bad cookie
				$this->app->cookie->removeCookie('comment_credentials');
			}
			break;

		case PinholePhoto::COMMENT_STATUS_LOCKED:
			$form->visible = false;
			$message = new SwatMessage(Pinhole::_('Comments are Locked'));
			$message->secondary_content =
				Pinhole::_('No new comments may be posted for this photo.');

			$ui->getWidget('message_display')->add($message,
				SwatMessageDisplay::DISMISS_OFF);

			break;

		case PinholePhoto::COMMENT_STATUS_CLOSED:
			$form->visible = false;
			$ui->getWidget('comments')->visible = false;
			break;
		}

		if ($show_thank_you) {
			switch ($this->photo->comment_status) {
			case PinholePhoto::COMMENT_STATUS_OPEN:
				$message = new SwatMessage(
					Pinhole::_('Your comment has been published.'));

				$this->ui->getWidget('message_display')->add($message,
					SwatMessageDisplay::DISMISS_OFF);

				break;

			case PinholePhoto::COMMENT_STATUS_MODERATED:
				$message = new SwatMessage(
					Pinhole::_('Your comment has been submitted.'));

				$message->secondary_content =
					Pinhole::_('Your comment will be published after being '.
						'approved by the site moderator.');

				$this->ui->getWidget('message_display')->add($message,
					SwatMessageDisplay::DISMISS_OFF);

				break;
			}
		}

		$this->buildCommentPreview();
	}

	// }}}
	// {{{ protected function buildCommentPreview()

	protected function buildCommentPreview()
	{
		if ($this->comment instanceof PinholeComment &&
			$this->ui->getWidget('preview_button')->hasBeenClicked()) {

			$button_tag = new SwatHtmlTag('input');
			$button_tag->type = 'submit';
			$button_tag->name = 'post_button';
			$button_tag->value = Pinhole::_('Post');

			$message = new SwatMessage(Pinhole::_(
				'Your comment has not yet been published.'));

			$message->secondary_content = sprintf(Pinhole::_(
				'Review your comment and press the <em>Post</em> button when '.
				'it’s ready to publish. %s'),
				$button_tag);

			$message->content_type = 'text/xml';

			$message_display =
				$this->ui->getWidget('preview_message_display');

			$message_display->add($message, SwatMessageDisplay::DISMISS_OFF);

			ob_start();

			$this->displayComment($this->comment);

			$comment_preview = $this->ui->getWidget('comment_preview');
			$comment_preview->content = ob_get_clean();
			$comment_preview->content_type = 'text/xml';

			$container = $this->ui->getWidget('comment_preview_container');
			$container->visible = true;

			$this->ui->getWidget('comments_frame')->visible = true;
		}
	}

	// }}}
	// {{{ protected function displayComment()

	protected function displayComment(PinholeComment $comment)
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = 'comment'.$comment->id;
		$div_tag->class = 'comment';

		if ($comment->photographer !== null) {
			$div_tag->class .= ' comment-photographer';
		}

		$div_tag->open();
		$this->displayCommentBody($comment);
		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayCommentBody()

	protected function displayCommentBody(PinholeComment $comment)
	{
		$heading_tag = new SwatHtmlTag('h4');
		$heading_tag->class = 'comment-title';
		$heading_tag->open();

		ob_start();
		$this->displayCommentAuthor($comment);
		$author = ob_get_clean();

		if ($author != '') {
			$elements[] = $author;
		}

		ob_start();
		$this->displayCommentPermalink($comment);
		$permalink = ob_get_clean();

		if ($permalink != '') {
			$elements[] = $permalink;
		}

		echo implode(' - ', $elements);

		$heading_tag->close();

		$this->displayCommentLink($comment);
		$this->displayCommentBodytext($comment);
	}

	// }}}
	// {{{ protected function displayCommentAuthor()

	protected function displayCommentAuthor(PinholeComment $comment)
	{
		if ($comment->photographer === null) {
			$span_tag = new SwatHtmlTag('span');
			$span_tag->setContent($comment->fullname);
			$span_tag->display();
		} else {
			$span_tag = new SwatHtmlTag('span');
			$span_tag->class = 'comment-photographer';
			$span_tag->setContent($comment->photographer->name);
			$span_tag->display();
		}
	}

	// }}}
	// {{{ protected function displayCommentLink()

	protected function displayCommentLink(PinholeComment $comment)
	{
		if ($comment->link != '') {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'comment-link';
			$div_tag->open();

			$anchor_tag = new SwatHtmlTag('a');
			$anchor_tag->href = $comment->link;
			$anchor_tag->class = 'comment-link';
			$anchor_tag->setContent($comment->link);
			$anchor_tag->display();

			$div_tag->close();
		}
	}

	// }}}
	// {{{ protected function displayCommentPermalink()

	protected function displayCommentPermalink(PinholeComment $comment)
	{
		$uri = 'photo/'.$this->photo->id;
		if (count($this->tag_list) > 0)
			$uri.= '?'.$this->tag_list->__toString();

		$uri.= '#comment'.$comment->id;

		$permalink_tag = new SwatHtmlTag('a');
		$permalink_tag->class = 'permalink';
		$permalink_tag->href = $uri;
		$permalink_tag->open();

		// display machine-readable date in UTC
		$abbr_tag = new SwatHtmlTag('abbr');
		$abbr_tag->class = 'comment-published';
		$abbr_tag->title =
			$comment->createdate->getDate(DATE_FORMAT_ISO_EXTENDED);

		// display human-readable date in local time
		$date = clone $comment->createdate;
		$date->convertTZ($this->app->default_time_zone);
		$abbr_tag->setContent($date->format(SwatDate::DF_DATE_TIME));
		$abbr_tag->display();

		$permalink_tag->close();
	}

	// }}}
	// {{{ protected function displayCommentBodytext()

	protected function displayCommentBodytext(PinholeComment $comment)
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'comment-content';
		$div_tag->setContent(
			SiteCommentFilter::toXhtml($comment->bodytext), 'text/xml');

		$div_tag->display();
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
