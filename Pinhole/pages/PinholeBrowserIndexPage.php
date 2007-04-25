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

		/*
		$this->layout->data->title = 
			SwatString::minimizeEntities($category->title);

		$this->layout->data->description = 
			SwatString::minimizeEntities($category->description);

		$this->layout->data->content= 
			SwatString::toXHTML($category->bodytext);
		*/
	}

	// }}}
}

?>
