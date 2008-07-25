<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminEdit.php';
require_once dirname(__FILE__).'/include/PinholeHeaderImageDisplay.php';

/**
 * Page for editing site instance settings
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeConfigEdit extends AdminEdit
{
	// {{{ protected properties

	protected $ui_xml = 'Pinhole/admin/components/Config/edit.xml';

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
	// {{{ protected function processUploadFile()

	protected function processUploadFile()
	{
		$old_file   = $this->app->config->pinhole->header_image;
		$widget = $this->ui->getWidget('header_image');
		$path = '../../files';

		if ($widget->isUploaded()) {
			$image_set = new SiteImageSet();
			$image_set->setDatabase($this->app->db);
			$image_set->loadByShortname('titles');

			$image = new SiteImage();
			$image->setDatabase($this->app->db);
			$image->setFileBase($path);
			$image->image_set = $image_set;
			$image->process($widget->getTempFileName());
		} else {
			$new_file_id = $id;
		}

		return $new_file_id;
	}

	// }}}
	// {{{ protected function createFile()

	protected function createFile(SwatFileEntry $file, $path)
	{
		/* TODO
		$now = new SwatDate();
		$now->toUTC();

		$class_name = SwatDBClassMap::get('PinholeFile');
		$pinhole_file = new $class_name();
		$pinhole_file->setDatabase($this->app->db);
		$pinhole_file->setFileBase($path);
		$pinhole_file->createFileBase($path);

		$pinhole_file->description = Pinhole::_('This Pinholes Header Image');
		$pinhole_file->visible    = true;
		$pinhole_file->filename   = $file->getUniqueFileName($path);
		$pinhole_file->mime_type  = $file->getMimeType();
		$pinhole_file->filesize   = $file->getSize();
		$pinhole_file->createdate = $now;
		$pinhole_file->instance   = $this->app->getInstanceId();
		$pinhole_file->save();

		$file->saveFile($path, $pinhole_file->filename);

		return $pinhole_file->id;
		*/
	}

	// }}}
	// {{{ protected function removeOldFile()

	protected function removeOldFile($id, $path)
	{
		/* TODO
		if ($id != '') {
			$class_name = SwatDBClassMap::get('PinholeFile');
			$old_file = new $class_name();
			$old_file->setDatabase($this->app->db);
			$old_file->load(intval($id));

			$old_file->setFileBase($path);
			$old_file->delete();
		}
		*/
	}

	// }}}
	// {{{ protected function saveData()

	protected function saveData()
	{
		$values = $this->ui->getValues(array(
			'site_title',
			'site_meta_description',
			'date_time_zone',
			'pinhole_search_engine_indexable',
			'pinhole_photos_per_page',
			'analytics_google_account',
		));

		$values['pinhole_search_engine_indexable'] =
			($values['pinhole_search_engine_indexable']) ? '1' : '0';

		foreach ($values as $key => $value) {
			$name = substr_replace($key, '.', strpos($key, '_'), 1);
			list($section, $title) = explode('.', $name, 2);

			$this->app->config->$section->$title = (string)$value;
		}

		if ($this->ui->getWidget('pinhole_passphrase')->value != '')
			$this->app->config->pinhole->passphrase =
				md5($this->ui->getWidget('pinhole_passphrase')->value);

		$this->app->config->save();
		$message = new SwatMessage(
			Pinhole::_('Your site settings have been saved.'));

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

		$this->ui->getWidget('pinhole_passphrase_field')->visible =
			($this->app->config->pinhole->passphrase !== null);
	}

	// }}}
	// {{{ protected function buildConfigValues()

	protected function buildConfigValues()
	{
		$values = array();
		$setting_keys = array(
			'site' => array(
				'title',
				'meta_description',
			),
			'pinhole' => array(
				'header_image',
				'photos_per_page',
				'search_engine_indexable',
			),
			'date' => array(
				'time_zone',
			),
			'analytics' => array(
				'google_account',
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
		$frame->title = Pinhole::_('Edit Site Settings');
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
		$this->navbar->createEntry(Pinhole::_('Edit Site Settings'));
	}

	// }}}
	// {{{ protected function loadData()

	protected function loadData()
	{
		return true;
	}

	// }}}
}

?>
