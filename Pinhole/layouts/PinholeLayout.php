<?php

require_once 'Site/layouts/SiteLayout.php';

/**
 * @package   Pinhole
 * @copyright 2008-2010 silverorange
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
		$js = $this->app->analytics->getGoogleAnalyticsInlineJavascript();
		if ($js != null) {
			Swat::displayInlineJavaScript($js);
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
		$instance_title = $this->app->config->site->title;

		// build html title (goes in html head)
		$page_title = $this->data->title;

		if ($this->data->html_title != '')
			$this->data->html_title = $this->data->html_title.' - '.
				$instance_title;
		elseif ($page_title == '')
			$this->data->html_title = $instance_title;
		else
			$this->data->html_title = $page_title.' - '.$instance_title;

		// build displayed title (top of page)
		$this->data->instance_title = $instance_title;
	}

	// }}}
}

?>
