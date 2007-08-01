<?php

require_once 'Swat/SwatString.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/exceptions/AdminNoAccessException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Pinhole/dataobjects/PinholePhotographer.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Pinhole/PinholeTagList.php';
require_once 'Pinhole/dataobjects/PinholeTagDataObjectWrapper.php';
require_once 'Pinhole/tags/PinholeTag.php';
require_once 'include/PinholePhotoTagEntry.php';

/**
 * Page for viewing photo details and editing
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $ui_xml = 'Pinhole/admin/components/Photo/edit.xml';

	/**
	 * @var PinholePhoto
	 */
	protected $photo;

	/**
	 * @var array
	 */
	protected $pending_photo_ids = array();

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		$this->initPhoto();
		$this->initStatuses();
		$this->initPendingPhotos();

		// setup tag entry control
		$tag_list = new PinholeTagList($this->app->db);
		$tag_list->setInstance($this->app->instance->getInstance());
		$sql = sprintf('select * from PinholeTag
			where instance = %s order by title',
			$this->app->db->quote(
				$this->app->instance->getInstance()->id, 'integer'));

		$tags = SwatDB::query($this->app->db, $sql,
			'PinholeTagDataObjectWrapper');
		
		foreach ($tags as $data_object) {
			$tag = new PinholeTag($data_object);
			$tag_list->add($tag);
		}

		$this->ui->getWidget('tags')->setTagList($tag_list);
		$this->ui->getWidget('tags')->setDatabase($this->app->db);

		// add hidden pending field if photo status is pending
		if ($this->photo->status == PinholePhoto::STATUS_PENDING)
			$this->ui->getWidget('edit_form')->addHiddenField(
				'pending', true);
	}

	// }}}
	// {{{ protected function initPhoto()

	protected function initPhoto()
	{
		$this->photo = new PinholePhoto();
		$this->photo->setDatabase($this->app->db);
		$this->photo->setInstance($this->app->instance->getInstance());

		if ($this->id === null) {
			throw new AdminNoAccessException(
				Pinhole::_('A Photo id is required.'));

		} else {
			if (!$this->photo->load($this->id))
				throw new AdminNotFoundException(
					sprintf(Pinhole::_('Photo with id “%s” not found.'),
					$this->id));
		}
	}

	// }}}
	// {{{ protected function initStatuses()

	protected function initStatuses()
	{
		$status = $this->ui->getWidget('status');

		if ($this->photo->status == PinholePhoto::STATUS_PENDING)
			$status->addOption(PinholePhoto::STATUS_PENDING,
				sprintf(Pinhole::_('Leave as %s'),
				PinholePhoto::getStatusTitle(PinholePhoto::STATUS_PENDING)));

		$status->addOption(PinholePhoto::STATUS_PUBLISHED,
			PinholePhoto::getStatusTitle(PinholePhoto::STATUS_PUBLISHED));
		$status->addOption(PinholePhoto::STATUS_UNPUBLISHED,
			PinholePhoto::getStatusTitle(PinholePhoto::STATUS_UNPUBLISHED));

		$this->ui->getWidget('status_field')->title = 
			Pinhole::_('Change Status to');
	}

	// }}}
	// {{{ protected function initPendingPhotos()

	protected function initPendingPhotos()
	{
		$pending = $this->ui->getWidget('edit_form')->getHiddenField('pending');
		if ($pending === null &&
			$this->photo->status != PinholePhoto::STATUS_PENDING)
			return;

		$instance = $this->app->instance->getInstance();

		$sql = sprintf('select id, title
			from PinholePhoto
			where PinholePhoto.status = %s and PinholePhoto.instance = %s
			order by PinholePhoto.upload_date, PinholePhoto.id',
			$this->app->db->quote(PinholePhoto::STATUS_PENDING,
				'integer'),
			$this->app->db->quote($instance->id, 'integer'));

		$rs = SwatDB::query($this->app->db, $sql, null);

		$found = false;
		while ($row = $rs->fetchRow(MDB2_FETCHMODE_OBJECT)) {
			if ($row->id == $this->photo->id)
				$found = true;
			elseif ($found)
				$this->pending_photo_ids[] = $row->id;
		}

		reset($this->pending_photo_ids);

		if (count($this->pending_photo_ids) > 0) {
			$this->ui->getWidget('proceed_button')->visible = true;
			$this->ui->getWidget('status_info')->content = 
				sprintf(Pinhole::ngettext(
					'%d pending photo left',
					'%d pending photos left',
					count($this->pending_photo_ids)),
					count($this->pending_photo_ids));
		}
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array(
			'title',
			'description',
			'photo_date',
			'status',
		));

		$this->photo->title     = $values['title'];
		$this->photo->description  = $values['description'];
		$this->photo->photo_date = $values['photo_date'];
		$this->photo->setStatus($values['status']);
		$this->photo->save();

		$tag_list = $this->ui->getWidget('tags')->getSelectedTagList();
		$tag_list = $tag_list->getByType('PinholeTag');
		$tag_ids = array();
		foreach ($tag_list as $tag)
			$tag_ids[] = $tag->id;

		// TODO: this deletes bindings
		SwatDB::updateBinding($this->app->db, 'PinholePhotoTagBinding',
			'photo', $this->id, 'tag', $tag_ids,
			'PinholeTag', 'id');

		$message = new SwatMessage(sprintf(
			Pinhole::_('“%s” has been saved.'),
			$this->photo->getTitle()));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		$pending = $this->ui->getWidget('edit_form')->getHiddenField('pending');
		$published = ($pending !== null && $this->photo->status ==
			PinholePhoto::STATUS_PUBLISHED);

		if ($this->ui->getWidget('proceed_button')->hasBeenClicked()) {
			$this->app->relocate('Photo/Edit?id='.
				current($this->pending_photo_ids));
		} elseif ($pending !== null && count($this->pending_photo_ids) > 0) {
			$this->app->relocate('Photo/Pending');
		} elseif ($this->published) {
			$this->app->relocate('Photo');
		} else {
			parent::relocate();
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		/*
		$image = $this->ui->getWidget('image');
		$dimension = $this->photo->getDimension('large');
		$image->image = '../'.$dimension->getUri();
		$image->width = $dimension->width;
		$image->height = $dimension->height;
		*/

		$thumb_dimension = $this->photo->getDimension('thumb');
		$large_dimension = $this->photo->getDimension('large');
		$thumbnail = $this->ui->getWidget('thumbnail');
		$thumbnail->image = '../'.$thumb_dimension->getUri();
		$thumbnail->width = $thumb_dimension->width;
		$thumbnail->height = $thumb_dimension->height;
		$thumbnail->preview_image = '../'.$large_dimension->getUri();
		$thumbnail->preview_width = $large_dimension->width;
		$thumbnail->preview_height = $large_dimension->height;

		/*
		$toolbar = $this->ui->getWidget('edit_toolbar');
		$toolbar->setToolLinkValues($this->photo->id);
		*/
	}

	// }}}
	// {{{ protected function buildButton()

	protected function buildButton()
	{
		$button = $this->ui->getWidget('submit_button');

		if ($this->photo->status == PinholePhoto::STATUS_PENDING)
			$this->ui->getWidget('proceed_button')->visible = true;
	}

	// }}}
	// {{{ protected function buildFrame()

	protected function buildFrame()
	{
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$this->navbar->createEntry(Pinhole::_('Edit'));
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->photo));
		$this->ui->getWidget('tags')->values = $this->photo->tags;
	}

	// }}}
}

?>
