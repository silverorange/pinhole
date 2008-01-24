<?php

require_once 'Site/dataobjects/SiteImageSet.php';
require_once 'Site/dataobjects/SiteInstance.php';

/**
 * A dataobject class for image sets with instance
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeImageSet extends SiteImageSet
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->registerInternalProperty('instance',
			SwatDBClassMap::get('SiteInstance'));
	}

	// }}}
}

?>
