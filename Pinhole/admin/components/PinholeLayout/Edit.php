<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Pinhole/dataobjects/PinholeLayouts.php';

/**
 * Page for editing the layout
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeLayoutEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $ui_xml = 'Pinhole/admin/components/PinholeLayout/edit.xml';

	/**
	 * @var PinholeLayout
	 */
	protected $edit_layout;

	protected $pagewidth_options = array('0' => '750px','1' => '950px',
											'2' => '100%');

	protected $sidebarposition_options = array('0' => 'left', '1' => 'right',
									 		'2' => 'bottom', '3' => 'none');

	protected $sidebarwidth_options = array('0' => '160px', '1' => '180px',
												'2' => '300px');


	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
		$this->initLayouts();
	}

	// }}}
	// {{{ protected function initLayout()

	protected function initLayouts()
	{
		$this->edit_layout = new PinholeLayouts();
		$this->edit_layout->setDatabase($this->app->db);

		if ($this->id === null) {
		} else {
			if (!$this->edit_layout->load($this->id))
				throw new AdminNotFoundException(
					sprintf(Pinhole::_('Layout with id “%s” not found.'),
						$this->id));
		}
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array(
			'title'
		));

		$pagewidth_radio       = $this->ui->getWidget('page_width');
		$sidebarposition_radio = $this->ui->getWidget('sidebar_position');
		$sidebarwidth_radio    = $this->ui->getWidget('sidebar_width');

		$this->edit_layout->pagewidth       = 
			$this->pagewidth_options[$pagewidth_radio->value];

		$this->edit_layout->sidebarposition = 
			$this->sidebarposition_options[$sidebarposition_radio->value];

		$this->edit_layout->sidebarwidth    =
			$this->sidebarwidth_options[$sidebarwidth_radio->value];
 
		$this->edit_layout->title = $values['title'];
		$this->edit_layout->save();

		$message = new SwatMessage(sprintf(
			Pinhole::_('“%s” has been saved.'),
			$this->edit_layout->title));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$pagewidth_radio       = $this->ui->getWidget('page_width');
		$sidebarposition_radio = $this->ui->getWidget('sidebar_position');
		$sidebarwidth_radio    = $this->ui->getWidget('sidebar_width');

		$pagewidth_radio->addOptionsByArray($this->pagewidth_options);
		$sidebarposition_radio->addOptionsByArray($this->sidebarposition_options);
		$sidebarwidth_radio->addOptionsByArray($this->sidebarwidth_options);

		$pagewidth_radio->value = 
			array_search($this->edit_layout->pagewidth ,
				$this->pagewidth_options);
		
		$sidebarposition_radio->value = 
			array_search($this->edit_layout->sidebarposition ,
				$this->sidebarposition_options);
		
		$sidebarwidth_radio->value = 
			array_search($this->edit_layout->sidebarwidth ,
				$this->sidebarwidth_options);

		$this->ui->setValues(get_object_vars($this->edit_layout));
	}

	// }}}
}

?>
