<?php

require_once 'Site/pages/SitePage.php';
require_once 'Sabre.autoload.php';
require_once 'Pinhole/PinholeDavDirectory.php';
require_once 'Pinhole/dataobjects/PinholeAdminUser.php';

/**
 * Web Dav Server Page
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeWebDavServerPage extends SitePage
{
	// process phase
	// {{{ public function process()

	public function process()
	{
		// initialize authentication
		$auth = new Sabre_HTTP_DigestAuth();
		$auth->setRealm($this->app->config->site->auth_realm);
		$auth->init();

		// authenticate and get correct user
		$email = $auth->getUsername();
		$class_name = SwatDBClassMap::get('PinholeAdminUser');
		$user = new $class_name();
		$user->setDatabase($this->app->db);
		if (!$user->loadFromEmail($email)
			|| !$auth->validateA1($user->digest_ha1)) {

			$auth->requireLogin();
			echo Pinhole::_('Authentication required')."\n";
			exit;
		}

		// create directory for account and object tree for dav server
		$root = new PinholeDavDirectory($this->app, $user);
		$tree = new Sabre_DAV_ObjectTree($root);

		// create server
		$server = new Sabre_DAV_Server($tree);
		$server->setBaseUri($this->getDavBaseUri());

		// don't save temp files in the database
		$tempFilePlugin = new Sabre_DAV_TemporaryFileFilterPlugin(
			$this->getDataDir('dav/temp'));

		$server->addPlugin($tempFilePlugin);

		// set up lock plugin
		$lockBackend = new Sabre_DAV_Locks_Backend_FS(
			$this->getDataDir('dav/locks'));

		$lockPlugin = new Sabre_DAV_Locks_Plugin($lockBackend);
		$server->addPlugin($lockPlugin);

		// also allow regular web browsing
		$browserPlugin = new Sabre_DAV_Browser_Plugin(false);
		$server->addPlugin($browserPlugin);

		// serve it up!
		$server->exec();
	}

	// }}}
	// {{{ protected function getDavBaseUri()

	protected function getDavBaseUri()
	{
		// get request uri
		$uri = trim($_SERVER['REQUEST_URI'], '/');
		$uri_exp = explode('/', $uri);

		// base uri is everything up until 'dav'
		$base_uri = '';
		foreach ($uri_exp as $part) {
			$base_uri.= '/'.$part;
			if ($part === 'dav') {
				break;
			}
		}

		return $base_uri;
	}

	// }}}
	// {{{ protected function getDataDir()

	protected function getDataDir($path)
	{
		$data_dir = '@data-dir@';

		if ($data_dir[0] == '@') {
			$data_dir = dirname(__FILE__).'/../../../data';
		} else {
			$data_dir.= DIRECTORY_SEPARATOR.'Pinhole';
		}

		$data_dir.= '/'.$path;

		if (!file_exists($data_dir)) {
			mkdir($data_dir, 0770, true);
		}

		return $data_dir;
	}

	// }}}
}

?>
