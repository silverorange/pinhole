<?php

/**
 * Displays a form for logging into a gallery
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePassphraseLoginGadget extends SiteGadget
{
	// {{{ protected function displayTitle()

	protected function displayTitle()
	{
		if ($this->app->session->isLoggedIn() ||
			$this->app->config->pinhole->passphrase == '')
			return;

		parent::displayTitle();
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		if ($this->app->session->isLoggedIn() ||
			$this->app->config->pinhole->passphrase == '')
			return;

		$login_button = new SwatButton();
		$login_button->title = Pinhole::_('Login');
		$login_button->classes[] = 'compact-button';

		$passphrase = new SwatPasswordEntry('passphrase');

		$login_form_field = new SwatFormField();
		$login_form_field->addChild($passphrase);
		$login_form_field->addChild($login_button);

		$login_form = new SwatForm('login_form');
		$login_form->addChild($login_form_field);
		$login_form->action = $this->app->getBaseHref(true).
			$this->app->config->pinhole->path.'login';

		$login_form->display();

		$this->html_head_entry_set->addEntrySet(
			$login_form->getHtmlHeadEntrySet());
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Pinhole::_('Login to View Private Photos'));
		$this->defineDescription(Pinhole::_(
			'Allows logging in to view private photos.'));
	}

	// }}}
}

?>
