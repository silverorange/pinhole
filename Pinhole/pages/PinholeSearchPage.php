<?php

require_once 'Swat/SwatNavBar.php';
require_once 'Swat/SwatString.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Site/pages/SitePathPage.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';

// TODO: this whole page needs to be built. It's just here for now for its
// constants. We need to think about how the search should be integrated into
// the site.

/**
 * Page for performing complex searches and displaying search results
 *
 * @package   Pinhole
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class PinholeSearchPage extends SitePathPage
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
