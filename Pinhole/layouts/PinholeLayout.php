<?php

require_once 'Site/layouts/SiteLayout.php';

/**
 * @package   Gallery
 * @copyright 2008 silverorange
 */
abstract class PinholeLayout extends SiteLayout
{
	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->startCapture('google_analytics');
		$this->displayGoogleAnalytics();
		$this->endCapture();
	}

	// }}}
	// {{{ protected function displayGoogleAnalytics()

	protected function displayGoogleAnalytics()
	{
		$google_account = $this->app->config->analytics->google_account;

		if ($google_account !== null) {
			$src = ($this->app->isSecure()) ?
				'https://ssl.google-analytics.com/urchin.js' :
				'http://www.google-analytics.com/urchin.js';

			$script_tag = new SwatHtmlTag('script');
			$script_tag->type = 'text/javascript';
			$script_tag->src = $src;
			$script_tag->setContent('');
			$script_tag->display();

			$javascript = sprintf(
				"_uacct = '%s';\n".
				"urchinTracker();",
				$google_account);

			Swat::displayInlineJavaScript($javascript);
		}
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->addHtmlHeadEntrySet(Pinhole::getHtmlHeadEntrySet($this->app));
		$this->finalizeTitle();
	}

	// }}}
	// {{{ protected function finalizeTitle()

	protected function finalizeTitle()
	{
		// build html title (goes in html head)
		$instance_title = $this->app->config->site->title;
		$page_title = $this->data->title;

		if ($page_title == '')
			$this->data->html_title = $instance_title;
		else
			$this->data->html_title = $page_title.' - '.$instance_title;

		// build displayed title (top of page)
		$this->data->instance_title = $instance_title;
	}

	// }}}
}

?>
