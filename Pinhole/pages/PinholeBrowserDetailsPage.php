<?php

require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/pages/PinholeBrowserPage.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeBrowserDetailsPage extends PinholeBrowserPage
{
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout,
		$tags = null, $photo_id = null)
	{
		parent::__construct($app, $layout, $tags);

		// TODO: use classmap
		$this->photo = new PinholePhoto();
		$this->photo->setDatabase($this->app->db);
		$this->photo->load(intval($photo_id));
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->data->title =
			SwatString::minimizeEntities($this->photo->title);

		$this->layout->startCapture('content');
		$this->displayPhoto();
		$this->displayPhotoDetails();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function displayPhoto()

	protected function displayPhoto()
	{
		$img_tag = new SwatHtmlTag('img');
		// TODO: use dimension objects
		$img_tag->src = $this->photo->getUri('large');
		$img_tag->class = 'pinhole-photo';
		$img_tag->display();
	}

	// }}}
	// {{{ protected function displayPhotoDetails()

	protected function displayPhotoDetails()
	{
		echo '<div class="pinhole-photo-details">';
			if (strlen($this->photo->description)) {
				$description_tag = new SwatHtmlTag('p');
				$description_tag->class = 'photo-description';
				$description_tag->setContent($this->photo->description);
				$description_tag->display();
			}
		echo '</div>';
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
