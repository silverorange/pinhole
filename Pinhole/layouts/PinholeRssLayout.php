<?php

require_once 'Pinhole/Pinhole.php';
require_once 'Site/layouts/SiteLayout.php';

/**
 * Layout for rss feeds in the Pinhole photo gallery package
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeRssLayout extends SiteLayout
{
	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();
		$this->data->feed = '';

		$date = new SwatDate();
		$this->data->current_time = $date->format('%Y-%m-%dT%H:%M:%S%O');
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		// build html title (used as rss feed title)
		$site_title = $this->app->config->site->title;
		$feed_title = $this->data->title;

		if (strlen($feed_title) > 0)
			$this->data->feed_title = $feed_title.' - '.$site_title;
		else
			$this->data->feed_title = $site_title;
	}

	// }}}
}

?>
