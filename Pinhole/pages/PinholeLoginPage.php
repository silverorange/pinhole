<?php

/**
 * Page for logging in with a passphrase
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeLoginPage extends SitePage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = __DIR__.'/login.xml';

	protected $ui;

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->ui = new SwatUI();
		$this->ui->loadFromXML($this->ui_xml);

		$form = $this->ui->getWidget('login_form');
		$form->action = $this->app->getBaseHref(true).'login';

		$this->ui->init();
	}

	// }}}
	// {{{ public function setReferrer()

	public function setReferrer($relative_uri)
	{
		$form = $this->ui->getWidget('login_form');
		$form->addHiddenField('referrer', $relative_uri);
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$form = $this->ui->getWidget('login_form');
		$form->process();

		if (!$form->isSubmitted() && isset($_GET['source'])) {
			$referrer = $form->getHiddenField('referrer');
			if ($referrer === null) {
				$referrer = $_GET['source'];
				if (isset($_GET['tags']))
					$referrer.= '?'.$_GET['tags'];

				$this->setReferrer($referrer);
			}
		}

		if ($form->isSubmitted() && !$form->hasMessage()) {
			$passphrase = $this->ui->getWidget('passphrase')->value;

			$this->app->session->activate();

			if ($this->app->session->login($passphrase)) {
				$uri = ($form->getHiddenField('referrer') === null) ? '' :
					$form->getHiddenField('referrer');

				if ($uri == 'login' || $uri == 'login?')
					$uri = '';

				$this->app->relocate($uri, true, true);
			} else {
				$message = new SwatMessage(Site::_('Login Incorrect'),
					SwatMessage::WARNING);

				$message->secondary_content = sprintf(
					'<ul><li>%s</li><li>%s</li></ul>',
					Site::_('Please check the spelling of the passphrase.'),
					sprintf(Site::_('Passphrase is case-sensitive. Make sure '.
						'your %sCaps Lock%s key is off.'),
						'<kbd>', '</kbd>'));

				$message->content_type = 'text/xml';
				$this->ui->getWidget('message_display')->add($message);
			}
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$form = $this->ui->getWidget('login_form');
		$this->layout->startCapture('content', true);
		$this->ui->display();
		$this->layout->endCapture();
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
