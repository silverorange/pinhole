<?php

require_once 'Swat/SwatString.php';
require_once 'Pinhole/pages/PinholeBrowserPage.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeBrowserIndexPage extends PinholeBrowserPage
{
	// init phase

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->startCapture('content');
		$this->displayPhotos();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function displayPhotos()

	protected function displayPhotos()
	{
		$photos = $this->tag_intersection->getPhotos();

		if (count($photos) == 0)
			return;

		echo '<ul class="photos">';

		foreach ($photos as $photo) {
			echo '<li>';
			//$link = $this->source.'/'.$category->shortname;
			echo $photo->title;
			echo '</li>';
		}

		echo '</ul>';
	}

	// }}}
}

?>
