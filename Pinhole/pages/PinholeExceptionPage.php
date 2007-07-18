<?php


require_once 'Swat/SwatUI.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Site/pages/SiteExceptionPage.php';

/**
 * @package   gallery
 * @copyright 2007 silverorange
 */
class PinholeExceptionPage extends SiteExceptionPage
{
	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$search_ui = new SwatUI();
		$search_ui->loadFromXML(
			dirname(__FILE__).'/browser-search.xml');

		$this->layout->startCapture('search_content');
		$search_ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function display()

	protected function display($status)
	{
		parent::display($status);
	}

	// }}}
	// {{{ protected function getSuggestions()

	protected function getSuggestions()
	{
		return array(
		Pinhole::_('If you typed the URL, check to make sure it is spelled correctly.'),
		Pinhole::_('You can <a href="tag">browse by tag</a> to find what you\'re looking for.'),
		);

		return $suggestions;
	}

	// }}}
}

?>
