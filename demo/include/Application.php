<?php

require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/layouts/PinholeLayout.php';
require_once 'Site/SiteWebApplication.php';
require_once 'Site/SiteConfigModule.php';
require_once 'Site/SiteDatabaseModule.php';
require_once 'Site/SiteAnalyticsModule.php';
require_once 'Site/SiteErrorLogger.php';
require_once 'Site/SiteExceptionLogger.php';
require_once 'Swat/exceptions/SwatException.php';
require_once 'Swat/SwatError.php';
require_once 'Swat/SwatForm.php';
require_once 'SwatDB/SwatDBClassMap.php';

SwatDBClassMap::addPath(dirname(__FILE__).'/dataobjects');

/**
 * Pinhole-based demo gallery
 *
 * @package   PinholeDemo
 * @copyright 2007-2010 silverorange
 */
class Application extends SiteWebApplication
{
	// {{{ protected function loadPage()

	protected function loadPage()
	{
		if (isset($this->config->locale))
			setlocale($this->config->locale);
		else
			setlocale(LC_ALL, 'en_CA.UTF-8');

		parent::loadPage();
	}

	// }}}
	// {{{ protected function resolvePage()

	/**
	 * Resolves page from a source string
	 * @return SwatPage A subclass of SwatPage is returned.
	 */
	protected function resolvePage($source)
	{
		$path = $this->explodeSource($source);

		if (count($path) == 0)
			// TODO: relocate since there is no separate front page right now
			$this->relocate('tag');
		else
			$tag = $path[0];

		switch ($tag) {
		case 'httperror':
			require_once 'Site/pages/SiteHttpErrorPage.php';
			$layout = new PinholeLayout($this,
				'Pinhole/layouts/xhtml/default.php');

			$page = new SiteHttpErrorPage($this, $layout);
			break;

		case 'exception':
			require_once 'Pinhole/pages/PinholeExceptionPage.php';
			$layout = new PinholeLayout($this,
				'Pinhole/layouts/xhtml/default.php');

			$page = new PinholeExceptionPage($this, $layout);
			break;

		default:
			require_once '../include/PageFactory.php';
			$factory = PageFactory::instance();
			$page = $factory->resolvePage($this, $source);
			break;
		}

		$page->setSource($source);
		return $page;
	}

	// }}}
	// {{{ protected function getDefaultModuleList()

	protected function getDefaultModuleList()
	{
		return array(
			'config'    => 'SiteConfigModule',
			'database'  => 'SiteDatabaseModule',
			'analytics' => 'SiteAnalyticsModule',
		);
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

		if (isset($config->exceptions->log_location))
			SwatException::setLogger(new SiteExceptionLogger(
				$config->exceptions->log_location,
				$config->exceptions->base_uri,
				$config->email->logging_address));

		if (isset($config->errors->log_location))
			SwatError::setLogger(new SiteErrorLogger(
				$config->errors->log_location,
				$config->errors->base_uri,
				$config->email->logging_address));

		SwatForm::$default_salt = $config->swat->form_salt;

		$this->database->dsn = $config->database->dsn;
		$this->setBaseUri($config->uri->base);
		$this->setSecureBaseUri($config->uri->secure_base);
		$this->default_time_zone =
			new Date_TimeZone($config->date->time_zone);

		$this->analytics->setGoogleAccount($config->analytics->google_account);
	}

	// }}}
}

?>
