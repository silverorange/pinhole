<?php

/**
 * Page for editing metadata
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeMetaDataEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $ui_xml = 'Pinhole/admin/components/MetaData/edit.xml';

	/**
	 * @var PinholeMetaData
	 */
	protected $metadata;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
		$this->initMetaData();
	}

	// }}}
	// {{{ protected function initMetaData()

	protected function initMetaData()
	{
		$this->metadata = new PinholeMetaData();
		$this->metadata->setDatabase($this->app->db);
		$this->metadata->instance = $this->app->getInstance();

		if ($this->id !== null) {
			if (!$this->metadata->load($this->id))
				throw new AdminNotFoundException(
					sprintf(Pinhole::_('MetaData with id “%s” not found.'),
						$this->id));
		}
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array(
			'title',
			'visible',
			'machine_tag',
		));

		$this->metadata->title       = $values['title'];
		$this->metadata->visible     = $values['visible'];
		$this->metadata->machine_tag = $values['machine_tag'];

		if ($this->metadata->id === null) {
			$this->metadata->shortname = $this->generateShortname(
				$this->metadata->title);
		}

		$flush_cache = ($this->metadata->isModified() &&
			$this->metadata->id !== null);

		$this->metadata->save();

		if (isset($this->app->memcache) && $flush_cache)
			$this->app->memcache->flushNs('photos');

		$message = new SwatMessage(sprintf(
			Pinhole::_('“%s” has been saved.'),
			$this->metadata->title));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function validateShortname()

	protected function validateShortname($shortname)
	{
		$valid = true;

		$class_name = SwatDBClassMap::get('PinholeMetaData');
		$metadata = new $class_name();
		$metadata->setDatabase($this->app->db);

		if ($metadata->loadByShortname($shortname,
			$this->app->getInstance())) {
			if ($metadata->id !== $this->metadata->id) {
				$valid = false;
			}
		}

		return $valid;
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->metadata));
	}

	// }}}
}

?>
