<?php

require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/dataobjects/PinholeInstance.php';
require_once 'Site/SiteMultipleInstanceModule.php';
require_once 'Site/exceptions/SiteException.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeMultipleInstanceModule extends SiteMultipleInstanceModule
{
	// {{{ public function __construct()

	/**
	 * Creates a new multiple instance module for a Pinhole application 
	 *
	 * @param SiteApplication $app the application this module belongs to.
	 *
	 * @throws SiteException if there is no database module loaded.
	 */
	public function __construct(SiteApplication $app)
	{
		if (!(isset($app->database) &&
			$app->database instanceof SiteDatabaseModule))
			throw new SiteException('The PinholeMultipleInstanceModule '.
				'requires a SiteDatabaseModule to be loaded first.');

		parent::__construct($app);
	}

	// }}}
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
		$this->instance = new PinholeInstance();
		$this->instance->setDatabase($this->app->database->getConnection());
		$this->instance->id        = 1;
		$this->instance->shortname = 'default';
		$this->instance->title     = Pinhole::_('Photo Gallery');
		$this->instance->enabled   = true;
	}

	// }}}
}

?>
