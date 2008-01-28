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
class PinholePinholePhotographerEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $ui_xml =
		'Pinhole/admin/components/PinholePhotographer/edit.xml';

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
		$this->photographer->instance = $this->app->instance->getInstance();

		if ($this->id !== null) {
			if (!$this->photographer->load($this->id))
				throw new AdminNotFoundException(
					sprintf(Pinhole::_('Photographer with id “%s” not found.'),
						$this->id));
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
			'status',
		));

		$this->photographer->fullname    = $values['fullname'];
		$this->photographer->description = $values['description'];
		$this->photographer->status      = $values['status'];
		$this->photographer->save();

		$message = new SwatMessage(sprintf(
			Pinhole::_('“%s” has been saved.'),
			$this->photographer->fullname));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->ui->getWidget('status')->addOptionsByArray(
			PinholePhotographer::getStatuses());
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->photographer));
	}

	// }}}
}

?>
