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
require_once 'Pinhole/pages/PinholeSearchPage.php';

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
	 * @var boolean
	 */
	protected $pending_photo = false;

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

		if ($this->photo->status == PinholePhoto::STATUS_PENDING)
			$this->pending_photo = true;

		// setup tag entry control
		$instance = $this->app->instance->getInstance();
		$tag_list = new PinholeTagList($this->app->db, $instance);
		$sql = sprintf('select * from PinholeTag
			where instance = %s
			order by title',
			$this->app->db->quote(
				$instance->id, 'integer'));

		/* TODO: use this once status is figured out
		$sql = sprintf('select * from PinholeTag
			where instance = %s
				and (status = %s or id in
					(select tag from PinholePhotoTagBinding
					where photo = %s))
			order by title',
			$this->app->db->quote($instance->id, 'integer'),
			$this->app->db->quote(PinholeTag::STATUS_ENABLED, 'integer'),
			$this->app->db->quote($this->photo->id, 'integer'));
		*/

		$tags = SwatDB::query($this->app->db, $sql,
			'PinholeTagDataObjectWrapper');
		
		foreach ($tags as $data_object) {
			$tag = new PinholeTag($data_object);
			$tag_list->add($tag);
		}

		$this->ui->getWidget('tags')->setTagList($tag_list);
		$this->ui->getWidget('tags')->setDatabase($this->app->db);
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
		$this->pending_pending = true;

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
	// {{{ protected function pendingPhotoCount()

	protected function pendingPhotoCount()
	{
		$pending_photos = $this->getPendingPhotos();

		return ($pending_photos === null) ? 0 :
			count($pending_photos);
	}

	// }}}
	// {{{ protected function upcomingPendingPhotoCount()

	protected function upcomingPendingPhotoCount()
	{
		$pending_photos = $this->getPendingPhotos();

		if ($pending_photos === null)
			return 0;

		$count = 0;
		$found = false;
		foreach ($pending_photos as $photo) {
			if ($photo->id == $this->photo->id)
				$found = true;
			elseif ($found)
				$count++;
		}

		return $count;
	}

	// }}}
	// {{{ protected function nextPendingPhoto()

	protected function nextPendingPhoto()
	{
		$pending_photos = $this->getPendingPhotos();

		if ($pending_photos === null)
			return false;

		$found = false;
		foreach ($pending_photos as $photo) {
			if ($photo->id == $this->photo->id)
				$found = true;
			elseif ($found)
				return $photo;
		}
	}

	// }}}
	// {{{ protected function getPendingPhotos()

	protected function getPendingPhotos()
	{
		static $pending_photos;

		if ($pending_photos === null) {
			$instance = $this->app->instance->getInstance();

			$sql = sprintf('select id, title
				from PinholePhoto
				where PinholePhoto.status = %s and PinholePhoto.instance = %s
				order by PinholePhoto.upload_date, PinholePhoto.id',
				$this->app->db->quote(PinholePhoto::STATUS_PENDING,
					'integer'),
				$this->app->db->quote($instance->id, 'integer'));

			$pending_photos =
				SwatDB::query($this->app->db, $sql, 'PinholePhotoWrapper');
		}

		return $pending_photos;
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

		if ($tag_list !== null) {
			$tag_list = $tag_list->getByType('PinholeTag');
			$tag_ids = array();
			foreach ($tag_list as $tag)
				$tag_ids[] = $tag->id;

			SwatDB::updateBinding($this->app->db, 'PinholePhotoTagBinding',
				'photo', $this->id, 'tag', $tag_ids,
				'PinholeTag', 'id');
		}

		$this->addToSearchQueue();

		$message = new SwatMessage(sprintf(
			Pinhole::_('“%s” has been saved.'),
			$this->photo->getTitle()));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue()
	{
		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->photo->id, 'integer'),
			$this->app->db->quote(PinholeSearchPage::TYPE_PHOTOS,
				'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->photo->id, 'integer'),
			$this->app->db->quote(PinholeSearchPage::TYPE_PHOTOS,
				'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		if ($this->pending_photo)
			$this->relocatePendingPhoto();
		else
			parent::relocate();
	}

	// }}}
	// {{{ protected function relocatePendingPhoto()

	protected function relocatePendingPhoto()
	{
		if ($this->ui->getWidget('proceed_button')->hasBeenClicked() &&
			$this->nextPendingPhoto() !== false)
			$this->app->relocate('Photo/Edit?id='.
				$this->nextPendingPhoto()->id);
		elseif ($this->pendingPhotoCount() > 0)
			$this->app->relocate('Photo/Pending');
		else
			$this->app->relocate('Photo');
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

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

		if ($this->upcomingPendingPhotoCount() > 0) {
			$this->ui->getWidget('proceed_button')->visible = true;
			$this->ui->getWidget('status_info')->content = 
				sprintf(Pinhole::ngettext(
					'%d pending photo left.',
					'%d pending photos left.',
					$this->upcomingPendingPhotoCount()),
					$this->upcomingPendingPhotoCount());
		}
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

		$tag_list = new PinholeTagList($this->app->db,
			$this->app->instance->getInstance());

		foreach ($this->photo->tags as $tag)
			$tag_list->add($tag);

		$this->ui->getWidget('tags')->setSelectedTagList($tag_list);
	}

	// }}}
}

?>
