<?php

require_once 'Site/pages/SitePage.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Pinhole/PinholeTagList.php';
require_once 'Pinhole/tags/PinholePageTag.php';
require_once 'Pinhole/dataobjects/PinholeImageDimension.php';
require_once 'XML/Atom/Feed.php';
require_once 'XML/Atom/Entry.php';

/**
 * Displays an Atom feed of photos
 *
 * Photos are displayed reverse-chronologically based on their publish-date.
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeAtomPage extends SitePage
{
	// {{{ protected properties

	/**
	 * The number of entries to display per page
	 *
	 * @var integer
	 */
	protected $page_size = 100;

	/**
	 * @var string
	 */
	protected $default_dimension = 'large';

	/**
	 * @var integer
	 */
	protected $page_number = 1;

	/**
	 * @var PinholeImageDimension
	 */
	protected $dimension;

	/**
	 * @var XML_Atom_Feed
	 */
	protected $feed;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new Atom post page
	 *
	 * @param SiteWebApplication $app the application.
	 * @param SiteLayout $layout
	 * @param string $dimension_shortname
	 */
	public function __construct(SiteWebApplication $app, SiteLayout $layout,
		array $arguments = array())
	{
		$layout = new SiteLayout($app, 'Site/layouts/xhtml/atom.php');

		parent::__construct($app, $layout, $arguments);

		$tags = SiteApplication::initVar('tags');
		$this->createTagList($tags);
		$this->dimension = $this->initDimension(
			$this->getArgument('dimension_shortname'));
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'dimension_shortname' => array(0, null),
		);
	}

	// }}}
	// {{{ protected function initDimension()

	protected function initDimension($shortname = null)
	{
		if ($shortname === null)
			$shortname = $this->default_dimension;

		$class_name = SwatDBClassMap::get('PinholeImageDimension');
		$dimension = new $class_name();
		$dimension->setDatabase($this->app->db);
		$dimension->loadByShortname('photos', $shortname);

		if ($dimension === null || !$dimension->selectable)
			throw new SiteNotFoundException(sprintf('Dimension “%s” is not '.
				'a selectable photo dimension', $shortname));

		return $dimension;
	}

	// }}}
	// {{{ protected function createTagList()

	protected function createTagList($tags)
	{
		$cache_module = isset($this->app->memcache) ?
			$this->app->memcache : null;

		$this->tag_list = new PinholeTagList($this->app->db,
			$this->app->getInstance(), $tags,
			$this->app->session->isLoggedIn(),
			$cache_module);

		$this->tag_list->setLoadTags(true);

		$page_tags = $this->tag_list->getByType('PinholePageTag');
		if (count($page_tags) == 0) {
			$range = new SwatDBRange($this->page_size);
		} else {
			// get first page tag if it exists and set current page
			$page_tags->rewind();
			$page_tag = $page_tags->current();
			$range = new SwatDBRange($this->page_size,
				$this->page_size * ($page_tag->getPageNumber() - 1));

			$this->page_number = $page_tag->getPageNumber();
		}

		foreach ($page_tags as $tag)
			$this->tag_list->remove($tag);

		$this->tag_list->setPhotoRange($range);
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		$this->buildAtomFeed();

		$this->layout->startCapture('content');
		$this->displayAtomFeed();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildAtomFeed()

	protected function buildAtomFeed()
	{
		$this->tag_list->setPhotoWhereClause(sprintf(
			'PinholePhoto.status = %s',
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer')));

		$this->tag_list->setPhotoOrderByClause(
			'PinholePhoto.publish_date desc, id desc');

		$site_base_href  = $this->app->getBaseHref();

		$this->feed = new XML_Atom_Feed($this->getPinholeBaseHref(),
			$this->app->config->site->title);

		$this->feed->addLink($site_base_href.$this->source, 'self',
			'application/atom+xml');

		$this->feed->setGenerator('Pinhole');
		$this->feed->setBase($site_base_href);

		//$author_uri = '';
		//$this->feed->addAuthor($this->post->author->name, $author_uri,
		//	$this->post->author->email);

		$this->buildAtomPagination();

		$photos = $this->tag_list->getPhotos($this->dimension->shortname,
			array('PinholePhoto.description'));

		foreach ($photos as $photo)
			$this->feed->addEntry($this->getEntry($photo));
	}

	// }}}
	// {{{ protected function buildAtomPagination()

	protected function buildAtomPagination()
	{
		// Feed paging. See IETF RFC 5005.
		$total_photos = $this->tag_list->getPhotoCount();

		$tag_string = (string) $this->tag_list;
		if ($tag_string != '')
			$tag_string.= '/';

		$uri = sprintf('%sfeed/%s%s%s',
			$this->getPinholeBaseHref(),
			$this->dimension->shortname,
			(strlen($tag_string) > 0) ? '?' : '',
			$tag_string);

		$this->feed->addLink($uri,
			'first', 'application/atom+xml');

		if ($tag_string == '')
			$uri.= '?';

		$last = ceil($total_photos / $this->page_size);

		$this->feed->addLink($uri.'page.number='.$last,
			'last', 'application/atom+xml');

		if ($this->page_number != 1) {
			$this->feed->addLink($uri.'page.number='.($this->page_number - 1),
				'previous', 'application/atom+xml');
		}

		if ($this->page_number != $last) {
			$this->feed->addLink($uri.'page.number='.($this->page_number + 1),
				'next', 'application/atom+xml');
		}
	}

	// }}}
	// {{{ protected function getPinholeBaseHref()

	protected function getPinholeBaseHref()
	{
		$site_base_href  = $this->app->getBaseHref();
		return $site_base_href.$this->app->config->pinhole->path;
	}

	// }}}
	// {{{ protected function getEntry()

	protected function getEntry(PinholePhoto $photo)
	{
		if (isset($this->app->memcache)) {
			$cache_key = sprintf('PinholeAtomPage.entry.%s.%s.%s.%s',
				(string) $this->tag_list, $photo->id,
				$this->dimension->shortname,
				$this->app->session->isLoggedIn() ? 'private' : 'public');

			$entry = $this->app->memcache->getNs('photos', $cache_key);
			if ($entry !== false)
				return $entry;
		}

		$uri = sprintf('%sphoto/%s/%s',
			$this->getPinholeBaseHref(),
			$photo->id,
			$this->dimension->shortname);

		if (count($this->tag_list) > 0)
			$uri.= '?'.$this->tag_list->__toString();

		$entry = new XML_Atom_Entry($uri, $photo->getTitle(),
			$photo->publish_date);

		if ($photo->hasDimension($this->dimension->shortname))
			$dimension = $this->dimension;
		else
			$dimension = $photo->getClosestSelectableDimensionTo(
				$this->dimension->shortname);

		$entry->setContent($this->getPhotoContent($photo, $dimension), 'html');

		foreach ($photo->getTags() as $tag)
			$entry->addCategory($tag->name, '', $tag->title);

		//$entry->addAuthor($author_name, $author_uri, $author_email);
		$entry->addAuthor($this->app->config->site->title);

		$uri = sprintf('%sphoto/%s',
			$this->getPinholeBaseHref(),
			$photo->id);

		$entry->addLink($uri, 'alternate', 'text/html');

		// add enclosure
		$photo_uri = $photo->getUri($dimension->shortname);
		$link = new XML_Atom_Link(
			$this->app->getBaseHref().$photo_uri,
			'enclosure',
			$photo->getMimeType($dimension->shortname));

		$link->setTitle($photo->getTitle());
		//$link->setLength();
		$entry->addLink($link);

		if (isset($this->app->memcache))
			$this->app->memcache->setNs('photos', $cache_key, $entry);

		return $entry;
	}

	// }}}
	// {{{ protected function getPhotoContent()

	protected function getPhotoContent(PinholePhoto $photo,
		PinholeImageDimension $dimension)
	{
		ob_start();

		$div_tag = new SwatHtmlTag('div');
		$div_tag->open();

		$img = $photo->getImgTag($dimension->shortname);
		$img->src = $this->app->getBaseHref().$img->src;
		$img->display();

		if ($photo->description !== null) {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->setContent($photo->description, 'text/xml');
			$div_tag->display();
		}

		$div_tag->close();

		return ob_get_clean();
	}

	// }}}
	// {{{ protected function displayAtomFeed()

	protected function displayAtomFeed()
	{
		echo $this->feed;
	}

	// }}}
}

?>
