<?php

/**
 * Index page for metadata
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoMetaData extends AdminIndex
{
	// {{{ protected properties

	protected $ui_xml =
		'Pinhole/admin/components/Photo/meta-data.xml';

	/**
	 * @var PinholePhoto
	 */
	protected $photo;

	// }}}

	// init phase
	// {{{ public function init()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
		$this->initPhoto();
	}

	// }}}
	// {{{ protected function initPhoto()

	protected function initPhoto()
	{
		$id = SiteApplication::initVar('id');

		$class_name = SwatDBClassMap::get('PinholePhoto');
		$this->photo = new $class_name();
		$this->photo->setDatabase($this->app->db);

		if ($id === null) {
			throw new AdminNoAccessException(
				Pinhole::_('A Photo id is required.'));
		} else {
			$instance_id = $this->app->getInstanceId();

			if (!$this->photo->load($id))
				throw new AdminNotFoundException(
					sprintf(Pinhole::_('Photo with id “%s” not found.'),
					$id));
			elseif ($this->photo->image_set->instance !== null &&
				$this->photo->image_set->instance->id != $instance_id)
				throw new AdminNotFoundException(
					sprintf(Pinhole::_('Photo with id “%s” loaded '.
						'in the wrong instance.'), $id));
		}
	}

	// }}}
	// {{{ protected function getMetaData()

	protected function getMetaData()
	{
		static $meta_data;

		if ($meta_data === null) {
			$sql = 'select title, id, value, machine_tag, visible
				from PinholeMetaData
				left outer join PinholePhotoMetaDataBinding
					on photo = %s and meta_data = id
				where PinholeMetaData.instance %s %s
				order by visible desc, displayorder, title';

			$instance_id = $this->app->getInstanceId();

			$sql = sprintf($sql,
				$this->app->db->quote($this->photo->id, 'integer'),
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'));

			$meta_data = SwatDB::query($this->app->db, $sql);
		}

		return $meta_data;
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		$form = $this->ui->getWidget('index_form');

		if ($form->isProcessed()) {
			$view = $form->getFirstDescendant('SwatView');
			$renderer = $view->getColumn('value_column')->getFirstRenderer();

			$modified = false;

			foreach ($this->getMetaData() as $data) {
				$widget = $renderer->getWidget($data->id);
				if ($widget->value != $data->value) {
					$modified = true;

					if ($data->value === null) {
						$sql = sprintf('insert into PinholePhotoMetaDataBinding
							(value, photo, meta_data) values (%s, %s, %s)',
							$this->app->db->quote($widget->value, 'text'),
							$this->app->db->quote($this->photo->id, 'integer'),
							$this->app->db->quote($data->id, 'integer'));
					} elseif ($widget->value === null) {
						$sql = sprintf('delete from PinholePhotoMetaDataBinding
							where photo = %s and meta_data = %s',
							$this->app->db->quote($widget->value, 'text'),
							$this->app->db->quote($this->photo->id, 'integer'),
							$this->app->db->quote($data->id, 'integer'));
					} else {
						$sql = sprintf('update PinholePhotoMetaDataBinding set
							value = %s where photo = %s and meta_data = %s',
							$this->app->db->quote($widget->value, 'text'),
							$this->app->db->quote($this->photo->id, 'integer'),
							$this->app->db->quote($data->id, 'integer'));
					}

					SwatDB::exec($this->app->db, $sql);
				}
			}

			if ($modified) {
				$this->app->messages->add(new SwatMessage(Pinhole::_(
					'Metadata Updated')));

				if (isset($this->app->memcache)) {
					$this->app->memcache->flushNs('photos');
				}
			}
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$toolbar = $this->ui->getWidget('toolbar');
		$toolbar->setToolLinkValues($this->photo->id);
	}

	// }}}
	// {{{ protected function getTableModel()

	/**
	 * Gets the metadata for display
	 *
	 * @return SwatTableModel with metadata information.
	 */
	protected function getTableModel(SwatView $view)
	{
		$metadata = $this->getMetaData();

		$store = new SwatTableStore();

		foreach ($metadata as $data) {
			$ds = new SwatDetailsView();
			$ds->title       = $data->title;
			$ds->value   = $data->value;
			$ds->id          = $data->id;
			$ds->visible     = $data->visible;
			$ds->machine_tag = $data->machine_tag;

			if ($ds->visible)
				$ds->group_title = Pinhole::_('Shown');
			else
				$ds->group_title = Pinhole::_('Not Shown');

			$store->add($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();
		$this->layout->navbar->createEntry($this->photo->getTitle(),
			'Photo/Edit?id='.$this->photo->id);
	}

	// }}}

}

?>
