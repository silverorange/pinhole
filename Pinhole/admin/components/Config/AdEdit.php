<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminEdit.php';

/**
 * Page for editing site instance settings
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeConfigAdEdit extends AdminEdit
{
	// {{{ protected properties

	protected $ui_xml = 'Pinhole/admin/components/Config/ad-edit.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}

	// process phase
	// {{{ protected function saveData()

	protected function saveData()
	{
		$values = $this->ui->getValues(array(
			'pinhole_ad_top',
			'pinhole_ad_bottom',
			'pinhole_ad_referers_only',
		));

		foreach ($values as $key => $value) {
			$name = substr_replace($key, '.', strpos($key, '_'), 1);
			list($section, $title) = explode('.', $name, 2);
			$this->app->config->$section->$title = (string)$value;
		}

		$this->app->config->save();
		$message = new SwatMessage(
			Pinhole::_('Your ad settings have been saved.'));

		$this->app->messages->add($message);

		return true;
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->buildConfigValues();
	}

	// }}}
	// {{{ protected function buildConfigValues()

	protected function buildConfigValues()
	{
		$values = array();
		$setting_keys = array(
			'pinhole' => array(
				'ad_top',
				'ad_bottom',
				'ad_referers_only',
			),
		);

		foreach ($setting_keys as $section => $keys) {
			foreach ($keys as $name) {
				$field_name = $section.'_'.$name;
				$values[$field_name] = $this->app->config->$section->$name;
			}
		}

		$this->ui->setValues($values);
	}

	// }}}
	// {{{ protected function buildFrame()

	protected function buildFrame()
	{
		$frame = $this->ui->getWidget('edit_frame');
		$frame->title = Pinhole::_('Edit Ad Settings');
	}

	// }}}
	// {{{ protected function buildButton()

	protected function buildButton()
	{
		$button = $this->ui->getWidget('submit_button');
		$button->setFromStock('apply');
	}

	// }}}
	// {{{ protected function buildFrame()

	protected function buildNavBar()
	{
		$this->navbar->createEntry(Pinhole::_('Edit Ad Settings'));
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadData()
	{
		return true;
	}

	// }}}
}

?>
