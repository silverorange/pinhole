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

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeBrowserDetailsPage extends PinholeBrowserPage
{
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

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout,
		array $arguments)
	{
		parent::__construct($app, $layout, $arguments);

		$this->ui_xml = 'Pinhole/pages/browser-details.xml';

		$this->createPhoto($this->getArgument('photo_id'));
		$this->dimension = $this->initDimension(
			$this->getArgument('dimension_shortname'));
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
		if (isset($this->app->memcache)) {
			$cache_key = 'PinholePhoto.'.$photo_id;
			$photo = $this->app->memcache->getNs('photos', $cache_key);
			if ($photo !== false) {
				$this->photo = $photo;
				$this->photo->setDatabase($this->app->db);
			}
		}

		if ($this->photo === null) {
			$photo_id = intval($photo_id);
			$photo_class = SwatDBClassMap::get('PinholePhoto');
			$this->photo = new $photo_class();
			$this->photo->setDatabase($this->app->db);
			$this->photo->load($photo_id);

			if (isset($this->app->memcache))
				$this->app->memcache->setNs(
					'photos', $cache_key, $this->photo);
		}

		if ($this->photo !== null) {
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
	// {{{ protected function initDimension()

	protected function initDimension($shortname = null)
	{
		if ($shortname === null) {
			if (isset($this->app->cookie->display_dimension))
				$shortname = $this->app->cookie->display_dimension;
			else
				$shortname = $this->default_dimension;
		}

		if (isset($this->app->memcache)) {
			$cache_key = sprintf(
				'PinholeBrowserDetailsPage.initDimension.%s.%s',
				$shortname, $this->photo->id);

			$dimension = $this->app->memcache->getNs('photos', $cache_key);
			if ($dimension !== false) {
				$dimension->setDatabase($this->app->db);
				return $dimension;
			}
		}

		$class_name = SwatDBClassMap::get('PinholeImageDimension');
		$display_dimension = new $class_name();
		$display_dimension->setDatabase($this->app->db);
		$display_dimension->loadByShortname('photos', $shortname);

		if ($display_dimension === null || !$display_dimension->selectable)
			throw new SiteNotFoundException(sprintf('Dimension “%s” is not '.
				'a selectable photo dimension', $shortname));

		$this->app->cookie->setCookie('display_dimension',
			$shortname, strtotime('+1 year'), '/',
			$this->app->getBaseHref());

		$dimension = $this->photo->getClosestSelectableDimensionTo($shortname);

		if (isset($this->app->memcache))
			$this->app->memcache->setNs('photos', $cache_key, $dimension);

		return $dimension;
	}

	// }}}

	// {{{ public function process()

	public function process()
	{
		parent::process();

		if ($this->photo->private && !$this->app->session->isLoggedIn()) {
			$this->app->replacePage('login');

			$referer = 'photo/'.$this->photo->id;

			if ($this->getArgument('dimension_shortname') !== null)
				$referer.= '/'.$this->getArgument('dimension_shortname');

			if (count($this->tag_list))
				$referer.= '?'.(string)$this->tag_list;

			$this->app->getPage()->setReferer($referer);
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$view = $this->ui->getWidget('photo_date_view');
		$view->data = $this->getPhotoDetailsStore();

		//$date = new SwatDate($this->photo->photo_date);
		//$date->convertTZByID($this->photo->photo_date_time_zone);

		$photo_date = $view->getField('photo_date');

		if ($this->photo->photo_date === null) {
			$photo_date->visible = false;
		} else {
			$date_links = $photo_date->getRenderer('date_links');
			$date_links->content_type = 'text/xml';
			$date_links->text = sprintf(Pinhole::_('<div id="photo_links">
				View photos taken on the same: '.
				'<a href="tag?date.date=%1$s-%2$s-%3$s">day</a>, '.
				'<a href="tag?date.week=%1$s-%2$s-%3$s">week</a>, '.
				'<a href="tag?date.month=%2$s/date.year=%1$s">month</a>, '.
				'<a href="tag?date.year=%1$s">year</a>.</div>'),
				$this->photo->photo_date->format('%Y'),
				$this->photo->photo_date->format('%m'),
				$this->photo->photo_date->format('%d'));
		}

		$tag_array = array();
		foreach ($this->photo->tags as $tag) {
			$a_tag = new SwatHtmlTag('a');
			$a_tag->href = $this->app->config->pinhole->path.'tag?'.$tag->name;
			$a_tag->setContent($tag->getTitle());
			$tag_array[] = (string) $a_tag;
		}

		if (count($tag_array) > 0) {
			$view->getField('tags')->getFirstRenderer()->text =
				implode(', ', $tag_array);
		} else {
			$view->getField('tags')->visible = false;
		}

		$description = $this->ui->getWidget('description');
		// Set to text/xml for now pending review in ticket #1159.
		$description->content_type = 'text/xml';
		$description->content = $this->photo->description;

		$username = $this->app->config->clustershot->username;
		if ($this->photo->for_sale && $username !== null || 1==1)
			$this->appendForSaleLink($description);

		$this->buildMetaData();
		$this->buildLayout();
		$this->buildPhotoNextPrev();
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
		$photo_meta_data = false;

		if (isset($this->app->memcache)) {
			$cache_key = sprintf('PinholeBrowserDetailsPage.MetaData.%s',
				$this->photo->id);

			$photo_meta_data = $this->app->memcache->getNs(
				'photos', $cache_key);
		}

		if ($photo_meta_data === false) {
			$photo_meta_data = $this->photo->meta_data;

			if (isset($this->app->memcache))
				$this->app->memcache->setNs('photos', $cache_key,
					$photo_meta_data);
		}

		$view = $this->ui->getWidget('photo_details_view');
		$view->visible = (count($photo_meta_data) > 0);

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
		$original_width = $this->photo->getWidth('original');
		$large_width = $this->photo->getWidth('large');
		$link_to_original = ($original_width > $large_width * 1.1);

		if ($link_to_original) {
			$a_tag = new SwatHtmlTag('a');
			$a_tag->href = $this->photo->getUri('original');
			$a_tag->title = Pinhole::_('View full-size image');
			$a_tag->open();
		}

		$img_tag = $this->photo->getImgTag(
			$this->dimension->shortname);

		$img_tag->class = 'pinhole-photo pinhole-photo-primary';
		$img_tag->display();

		if ($link_to_original)
			$a_tag->close();
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$this->displayPhoto();
		$this->displayDimensions();
		parent::displayContent();
	}

	// }}}
	// {{{ protected function displayDimensions()

	protected function displayDimensions()
	{
		$dimensions = $this->getSelectableDimensions();

		if (count($dimensions) <= 1)
			return;

		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'pinhole-photo-dimensions';
		$div_tag->open();

		echo Pinhole::_('Dimensions: ');

		$list = array();

		foreach ($dimensions as $dimension) {
			ob_start();

			if ($dimension->id == $this->dimension->id) {
				$span_tag = new SwatHtmlTag('span');
				$span_tag->setContent($dimension->title);
				$span_tag->display();
			} else {
				$a_tag = new SwatHtmlTag('a');

				if ($dimension->shortname == 'original')
					$a_tag->href = $this->photo->getUri('original');
				else
					$a_tag->href = 'photo/'.$this->photo->id.'/'.
						$dimension->shortname;

				if (count($this->tag_list) > 0)
					$a_tag->href.= '?'.$this->tag_list->__toString();

				$a_tag->setContent($dimension->title);
				$a_tag->display();
			}

			$list[] = ob_get_clean();
		}

		echo implode(', ', $list);

		$div_tag->close();
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
		echo '<div id="cluster_shot_price_link">';

		$a_tag = new SwatHtmlTag('a');
		$a_tag->setContent(Pinhole::_('Buy this photo'));
		$a_tag->href = 'http://www.clustershot.com/user/'.$username.
			'/relocate?uri='.urlencode($uri);

		$a_tag->display();

		printf(Pinhole::_(' on %s'),
			'<a href="http://www.clustershot.com">ClusterShot.com</a>');

		echo '</div>';

		/*
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
