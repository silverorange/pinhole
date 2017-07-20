<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatDetailsViewField.php';
require_once 'Swat/SwatTextCellRenderer.php';
require_once 'Swat/SwatLinkCellRenderer.php';
require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatString.php';
require_once 'Site/SiteViewFactory.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/PinholeCommentUi.php';
require_once 'Pinhole/pages/PinholeBrowserPage.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'Pinhole/dataobjects/PinholeComment.php';

/**
 * @package   Pinhole
 * @copyright 2007-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeBrowserDetailsPage extends PinholeBrowserPage
{
	// {{{ protected properties

	/**
	 * @var SiteCommentUi
	 */
	protected $comment_ui;

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
			} elseif ($this->photo->status != PinholePhoto::STATUS_PUBLISHED) {
				throw new SiteNotFoundException('Photo is not published yet.');
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

		$uri = 'photo/'.$this->photo->id;
		if (count($this->tag_list) > 0)
			$uri.= '?'.$this->tag_list->__toString();

		$this->comment_ui = new PinholeCommentUi($this->app, $this->photo,
			$uri);

		$this->comment_ui->init();
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

		if ($this->photo->private && !$this->app->session->isLoggedIn()) {
			// TODO: this could be simplified by simply redirecting to
			// the secure page with the same uri. All of the setReferrer
			// could then be removed from PinholeLoginPage

			$this->app->replacePage('login');

			$referer = 'photo/'.$this->photo->id;

			if ($this->getArgument('dimension_shortname') !== null)
				$referer.= '/'.$this->getArgument('dimension_shortname');

			if (count($this->tag_list))
				$referer.= '?'.(string)$this->tag_list;

			$this->app->getPage()->setReferrer($referer);
		}

		$this->comment_ui->process();
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->buildDetailsView();
		$this->buildComments();
		$this->buildLayout();
		$this->buildPhotoNextPrev();

		ob_start();

		$title = $this->photo->getTitle();
		if ($title != '') {
			$h1 = new SwatHtmlTag('h1');
			$h1->setContent($title);
			$h1->display();
		}

		if ($this->photo->description != '') {
			// Set to text/xml for now pending review in ticket #1159.
			$div = new SwatHtmlTag('div');
			$div->setContent($this->photo->description, 'text/xml');
			$div->display();
		}

		$description = $this->ui->getWidget('description');
		$description->content_type = 'text/xml';
		$description->content = ob_get_clean();

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
		$store = new SwatDetailsStore($this->photo);

		if ($this->photo->gps_latitude !== null &&
			$this->photo->gps_longitude !== null) {

			$store->map_link = sprintf('%smap/%s',
				$this->app->config->pinhole->path,
				$this->photo->id);

			if (count($this->tag_list) > 0) {
				$store->map_link.= '?'.$this->tag_list->__toString();
			}
		} else {
			$store->map_link = null;
		}

		return $store;
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
		$view = $this->ui->getWidget('photo_details_view');
		$view->data = $this->getPhotoDetailsStore();

		$this->buildDimensions($view);
		$this->buildPhotoDate($view);
		$this->buildTags($view);
		$this->buildMetaData($view);
		$this->buildMapLink($view);
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
			$date->convertTZById($this->photo->photo_time_zone);

			$date_links = $photo_date->getRenderer('date_links');
			$date_links->content_type = 'text/xml';
			$date_links->text = sprintf(Pinhole::_('
				(view photos taken on the same: '.
				'<a href="tag?date.date=%1$s-%2$s-%3$s">day</a>, '.
				'<a href="tag?date.week=%1$s-%2$s-%3$s">week</a>, '.
				'<a href="tag?date.month=%2$s/date.year=%1$s">month</a>, '.
				'<a href="tag?date.year=%1$s">year</a>)'),
				$date->formatLikeIntl('yyyy'),
				$date->formatLikeIntl('MM'),
				$date->formatLikeIntl('dd'));
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
			$tag_array[] = (string)$a_tag;
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

				if ($dimension->shortname == 'original') {
					$a_tag->href = $this->photo->getUri('original');
				} else {
					$a_tag->href = $this->app->config->pinhole->path.
						'photo/'.$this->photo->id.'/'.$dimension->shortname;
				}

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
	// {{{ protected function buildMetaData()

	protected function buildMetaData(SwatDetailsView $view)
	{
		$photo_meta_data = $this->photo->meta_data;
		$field = $view->getField('meta_data');

		if (count($photo_meta_data) == 0)
			$field->visible = false;

		$camera = null;
		foreach ($this->photo->meta_data as $meta_data) {
			if ($meta_data->shortname == 'model') {
				$camera = $meta_data->value;
			}
		}

		$camera_renderer = $field->getRenderer('meta_data_camera');

		if ($camera === null)
			$camera_renderer->visible = false;
		else
			$camera_renderer->text = sprintf(Pinhole::_('Taken with a %s'),
				$camera).' | ';

		$renderer = $field->getRenderer('meta_data_widget');
		$widget = $renderer->getPrototypeWidget();

		$meta_data_view = $widget->getChild('meta_data_disclosure')->getChild('meta_data_view');

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

			$renderer->text = PinholePhotoMetaDataBinding::getFormattedValue(
				$meta_data->shortname, $meta_data->value);

			$meta_data_view->appendField($field);
			$field->addRenderer($renderer);
		}

	}

	// }}}
	// {{{ protected function buildMapLink()

	protected function buildMapLink(SwatDetailsView $view)
	{
		$view->getField('map_link')->visible =
			($this->photo->gps_latitude !== null &&
			$this->photo->gps_longitude !== null);
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
			$status = $this->app->config->pinhole->default_comment_status;
		} else {
			// comments are globally turned off
			$status = SiteCommentStatus::CLOSED;
		}

		if ($status !== SiteCommentStatus::CLOSED) {
			$comments = $this->photo->getVisibleComments();

			if (count($comments) > 0) {
				$this->ui->getWidget('comments_frame')->visible = true;

				ob_start();
				$div_tag = new SwatHtmlTag('div');
				$div_tag->id = 'comments';
				$div_tag->class = 'photo-comments';
				$div_tag->open();

				$view = SiteViewFactory::get($this->app, 'photo-comment');
				foreach ($comments as $comment) {
					$view->display($comment);
				}

				$div_tag->close();

				$this->ui->getWidget('comments')->content = ob_get_clean();
			}

			ob_start();
			$this->comment_ui->display();
			$this->ui->getWidget('comments_ui')->content = ob_get_clean();
		}
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntrySet(
			$this->comment_ui->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
