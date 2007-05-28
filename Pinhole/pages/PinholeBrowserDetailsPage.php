<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatUI.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/pages/PinholeBrowserPage.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeBrowserDetailsPage extends PinholeBrowserPage
{

	protected $photo;
	protected $details_ui;
	protected $details_ui_xml = 'Pinhole/pages/photo-details-view.xml';

	// init phase
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout,
		$photo_id = null, $tags = null)
	{
		parent::__construct($app, $layout, $tags);

		// TODO: use classmap
		$this->photo = new PinholePhoto();
		$this->photo->setDatabase($this->app->db);
		$this->photo->load(intval($photo_id));
	}

	// }}}
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->details_ui = new SwatUI();
		$this->details_ui->mapClassPrefixToPath('Pinhole', 'Pinhole');
		$this->details_ui->loadFromXML($this->details_ui_xml);
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$view = $this->details_ui->getWidget('photo_details_view');
		$view->data = $this->getPhotoDetailsStore();

		/* Set YUI Grid CSS class for one full-width column on details page */
		$this->layout->data->yui_grid_class = 'yui-t7';

		$this->layout->data->title =
			SwatString::minimizeEntities($this->photo->title);

		$this->layout->startCapture('content');
		$this->displayPhoto();
		$this->displayPhotoDetails();
		$this->details_ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function getPhotoDetailsStore()

	protected function getPhotoDetailsStore()
	{
		$store = new SwatDetailsStore($this->photo);

		return $store;
	}

	// }}}
	// {{{ protected function displayPhoto()

	protected function displayPhoto()
	{
		$img_tag = new SwatHtmlTag('img');
		$img_tag->src = $this->photo->getDimension('large')->getUri();
		$img_tag->width = $this->photo->getDimension('large')->width;
		$img_tag->height = $this->photo->getDimension('large')->height;
		$img_tag->alt = $this->photo->title;
		$img_tag->class = 'pinhole-photo';
		$img_tag->display();
	}

	// }}}
	// {{{ protected function getTagListTags()

	protected function getTagListTags()
	{
		$tags = new PinholeTagWrapper();
		$intersection_tags = parent::getTagListTags();
		$photo_tags = $this->photo->tags;
		foreach ($intersection_tags as $tag)
			if ($photo_tags->getByIndex($tag->id) !== null)
				$tags->add($tag);

		return $tags;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/styles/pinhole-browser-details-page.css',
			Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
