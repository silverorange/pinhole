<?php

require_once 'Pinhole/pages/PinholePage.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Pinhole/dataobjects/PinholeTagWrapper.php';

require_once 'Swat/SwatNavBar.php';
require_once 'Swat/SwatString.php';
require_once 'SwatDB/SwatDB.php';

// TODO: this whole page needs to be built. It's just here for now for its
// constants. We need to think about how the search should be integrated into
// the site.

/**
 * Page for performing complex searches and displaying search results
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
abstract class PinholeSearchPage extends PinholePage
{
	// {{{ class constants

	/**
	 * Type for product search
	 */
	const TYPE_PHOTOS = 'photos';

	/**
	 * Type for tag search
	 */
	const TYPE_TAGS = 'tags';

	// }}}
}

?>
