<?php

require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Pinhole/pages/PinholeBrowserPage.php';
require_once 'Pinhole/tags/PinholePageTag.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeBrowserIndexPage extends PinholeBrowserPage
{
	// {{{ protected properties

	/**
	 * @var integer
	 */
	protected $page_size = 50;

	/**
	 * @var integer
	 */
	protected $page_number = 1;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout,
		$tags = '')
	{
		parent::__construct($app, $layout, $tags);
		$this->ui_xml = 'Pinhole/pages/browser-index.xml';
	}

	// }}}
	// {{{ protected function createTagList()

	protected function createTagList($tags)
	{
		parent::createTagList($tags);

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
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->buildDateTagBrowser();
		$this->buildPhotoPagination();

		$view = $this->ui->getWidget('photo_view');
		$view->model = $this->getPhotoTableStore();
	}

	// }}}
	// {{{ protected function buildDateTagBrowser()

	protected function buildDateTagBrowser()
	{
		$date_tag_browser = $this->ui->getWidget('date_tag_browser');
		$date_tag_browser->setTagList($this->tag_list);
		$date_tag_browser->setDatabase($this->app->db);

		if (count($this->getPath()) > 0)
			$date_tag_browser->base = $this->getPath().'/'.$date_tag_browser->base;
	}

	// }}}
	// {{{ protected function buildPhotoPagination()

	protected function buildPhotoPagination()
	{
		$pagination = $this->ui->getWidget('pagination');
		$pagination->total_records = $this->tag_list->getPhotoCount();
		$pagination->page_size = $this->page_size;
		$pagination->setCurrentPage($this->page_number);
		if (count($this->tag_list) == 0)
			$tag_path = '';
		else
			$tag_path = $this->tag_list->__toString().'/';

		$pagination->link = 'tag?';
		$pagination->link.= str_replace('%', '%%', $tag_path);
		$pagination->link.= 'page.number=%d';
	}

	// }}}
	// {{{ protected function getPhotoTableStore()

	protected function getPhotoTableStore()
	{
		if (count($this->tag_list) == 0)
			$tag_path = '';
		else
			$tag_path = '?'.$this->tag_list->__toString();

		$photos = $this->tag_list->getPhotos();

		$store = new SwatTableStore();

		foreach ($photos as $photo) {
			$ds = new SwatDetailsStore();

			if (count($this->getPath()) > 0)
				$ds->root_path = $this->getPath().'/';
			else
				$ds->root_path = '';

			$ds->path = $photo->id.$tag_path;
			$ds->photo = $photo;
			$store->add($ds);
		}

		return $store;
	}

	// }}}
}

?>
