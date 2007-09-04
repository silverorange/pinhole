<?php

require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/dataobjects/PinholeInstance.php';
require_once 'Site/SiteMultipleInstanceModule.php';
require_once 'Site/exceptions/SiteException.php';
require_once 'SwatDB/SwatDBClassMap.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeMultipleInstanceModule extends SiteMultipleInstanceModule
{
	// {{{ public function init()

	/**
	 * Initializes this module
	 *
	 * The default implementation in Pinhole creates a default instance object
	 * and assigns it to the internal <i>$instance</i> property. Subsequent
	 * calls to <cpde>$this->app->instance->getInstance()</code> return the
	 * {@link PinholeInstance} instance object.
	 */
	public function init()
	{
		// create a default instance (for single instance sites)
		$class_name = SwatDBClassMap::get('PinholeInstance');
		$this->instance = new $class_name();
		$this->instance->setDatabase($this->app->database->getConnection());
		$this->instance->id        = 1;
		$this->instance->shortname = 'default';
		$this->instance->title     = Pinhole::_('Photo Gallery');
		$this->instance->enabled   = true;
	}

	// }}}
	// {{{ public function depends()

	/**
	 * Gets the module features this module depends on
	 *
	 * The pinhole multiple instance module depends on the SiteDatabaseModule
	 * feature.
	 *
	 * @return array an array of features this module depends on.
	 */
	public function depends()
	{
		return array('SiteDatabaseModule');
	}

	// }}}
}

?>
