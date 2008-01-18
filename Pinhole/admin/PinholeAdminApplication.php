<?php

require_once 'Site/SiteMultipleInstanceModule.php';
require_once 'Admin/AdminApplication.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/dataobjects/PinholeAdminUser.php';

/**
 * Web application class for an administering Pinhole
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeAdminApplication extends AdminApplication
{
	// {{{ public function __construct()

	public function __construct($id, $filename)
	{
		parent::__construct($id, $filename);
		$this->config->session->name.= 'admin';
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	/**
	 * Gets the list of default modules to load for this applicaiton
	 *
	 * @return array
	 * @see    AdminApplication::getDefaultModuleList()
	 */
	protected function getDefaultModuleList()
	{
		$modules = parent::getDefaultModuleList();
		$modules['instance'] = 'SiteMultipleInstanceModule';

		return $modules;
	}

	// }}}
	// {{{ protected function addConfigDefinitions()

	/**
	 * Adds configuration definitions to the config module of this application
	 *
	 * @param SiteConfigModule $config the config module of this application to
	 *                                  witch to add the config definitions.
	 */
	protected function addConfigDefinitions(SiteConfigModule $config)
	{
		parent::addConfigDefinitions($config);
		$config->addDefinitions(Pinhole::getConfigDefinitions());
	}

	// }}}
	// {{{ protected function configure()

	/**
	 * Configures modules of this application before they are initialized
	 *
	 * @param SiteConfigModule $config the config module of this application to
	 *                                  use for configuration other modules.
	 */
	protected function configure(SiteConfigModule $config)
	{
		parent::configure($config);

		$this->addComponentIncludePath('Pinhole/admin/components', 'Pinhole');
		$this->addComponentIncludePath('Site/admin/components', 'Site');

		if (isset($config->exceptions->log_location))
			SwatException::setLogger(new SiteExceptionLogger(
				$config->exceptions->log_location,
				$config->exceptions->base_uri));

		if (isset($config->errors->log_location))
			SwatError::setLogger(new SiteErrorLogger(
				$config->errors->log_location, $config->errors->base_uri));

		SwatForm::$default_salt = $config->swat->form_salt;

		$this->database->dsn = $config->database->dsn;
		$this->setBaseUri($config->uri->base.'admin/');
		$this->setSecureBaseUri($config->uri->secure_base.'admin/');
		$this->cookie->setSalt($config->cookies->salt);
		$this->default_time_zone =
			new Date_TimeZone($config->date->time_zone);

		$this->default_locale = $config->i18n->locale;
	}

	// }}}
	// {{{ protected function initModules()

	/**
	 * Initializes all modules in this application
	 */
	protected function initModules()
	{
		parent::initModules();

		$this->title = sprintf('%s Admin', $this->config->site->title);
	}

	// }}}
}

?>
