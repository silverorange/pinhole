<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Pinhole/dataobjects/PinholePhotographer.php';

/**
 * Page for editing a photographer
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotographerEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $ui_xml = 'Pinhole/admin/components/PinholePhotographer/edit.xml';

	/**
	 * @var PinholePhotographer
	 */
	protected $photographer;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
		$this->initPhotographer();
	}

	// }}}
	// {{{ protected function initPhotographer()

	protected function initPhotographer()
	{
		$this->photographer = new PinholePhotographer();
		$this->photographer->setDatabase($this->app->db);

		if ($this->id === null) {
		} else {
			if (!$this->photographer->load($this->id))
				throw new AdminNotFoundException(
					Pinhole::_('Photographer with id “%s” not found.'));
		}
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array(
			'fullname',
			'description',
			'enabled',
			'archived',
		));

		$this->photographer->fullname    = $values['fullname'];
		$this->photographer->description = $values['description'];
		$this->photographer->enabled     = $values['enabled'];
		$this->photographer->archived    = $values['archived'];
		$this->photographer->save();

		$message = new SwatMessage(sprintf(
			Pinhole::_('“%s” has been saved.'),
			$this->photographer->fullname));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->photographer));
	}

	// }}}
}

?>
