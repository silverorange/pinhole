<?php

require_once 'Swat/SwatString.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/exceptions/AdminNoAccessException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'NateGoSearch/NateGoSearch.php';
require_once 'Pinhole/dataobjects/PinholePhotographer.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Pinhole/PinholeTagList.php';
require_once 'Pinhole/dataobjects/PinholeTagDataObjectWrapper.php';
require_once 'Pinhole/tags/PinholeTag.php';
require_once 'Pinhole/pages/PinholeSearchPage.php';
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
	 * @var boolean
	 */
	protected $pending_photo = false;

	/**
	 * @var array
	 */
	protected $pending_photos = array();

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

		if ($this->photo->status == PinholePhoto::STATUS_PENDING) {
			$this->pending_photo = true;
			$this->pending_photos = $this->getPendingPhotos();
		}

		// setup tag entry control
		$instance_id = $this->app->instance->getId();
		$tag_list = new PinholeTagList($this->app->db,
			$this->app->instance->getInstance());

		$sql = sprintf('select * from PinholeTag
			where instance %s %s
			order by title',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		/* TODO: use this once status is figured out
		$sql = sprintf('select * from PinholeTag
			where instance %s %s
				and (status = %s or id in
					(select tag from PinholePhotoTagBinding
					where photo = %s))
			order by title',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(PinholeTag::STATUS_ENABLED, 'integer'),
			$this->app->db->quote($this->photo->id, 'integer'));
		*/

		$tags = SwatDB::query($this->app->db, $sql,
			'PinholeTagDataObjectWrapper');

		foreach ($tags as $data_object)
			$tag_list->add(new PinholeTag($data_object));

		$this->ui->getWidget('tags')->setTagList($tag_list);
		$this->ui->getWidget('tags')->setDatabase($this->app->db);
	}

	// }}}
	// {{{ protected function initPhoto()

	protected function initPhoto()
	{
		$this->photo = new PinholePhoto();
		$this->photo->setDatabase($this->app->db);

		if ($this->id === null) {
			throw new AdminNoAccessException(
				Pinhole::_('A Photo id is required.'));
		} else {
			$instance_id = $this->app->instance->getId();

			if (!$this->photo->load($this->id))
				throw new AdminNotFoundException(
					sprintf(Pinhole::_('Photo with id “%s” not found.'),
					$this->id));
			elseif ($this->photo->image_set->instance->id != $instance_id)
				throw new AdminNotFoundException(
					sprintf(Pinhole::_('Photo with id “%s” loaded '.
						'in the wrong instance.'),
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
	// {{{ protected function pendingPhotoCount()

	protected function pendingPhotoCount()
	{
		return (count($this->pending_photos));
	}

	// }}}
	// {{{ protected function upcomingPendingPhotoCount()

	protected function upcomingPendingPhotoCount()
	{
		$count = 0;
		$found = false;
		foreach ($this->pending_photos as $photo) {
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
		$found = false;

		foreach ($this->pending_photos as $photo) {
			echo $photo->id.'<br />';
			if ($photo->id == $this->photo->id)
				$found = true;
			elseif ($found)
				return $photo;
		}

		return false;
	}

	// }}}
	// {{{ protected function getPendingPhotos()

	protected function getPendingPhotos()
	{
		$instance_id = $this->app->instance->getId();

		$sql = sprintf('select PinholePhoto.id, PinholePhoto.title
			from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where PinholePhoto.status = %s and ImageSet.instance %s %s
			order by PinholePhoto.upload_date, PinholePhoto.id',
			$this->app->db->quote(PinholePhoto::STATUS_PENDING, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		return SwatDB::query($this->app->db, $sql, 'PinholePhotoWrapper');
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

		// turns the date back into UTC
		$photo_date = new SwatDate($values['photo_date']);
		$photo_date->setTZbyID($this->photo->photo_time_zone);
		$photo_date->toUTC();

		$this->photo->title       = $values['title'];
		$this->photo->description = $values['description'];
		$this->photo->photo_date  = $photo_date;
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
		$type = NateGoSearch::getDocumentType($this->app->db, 'photo');

		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->photo->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->photo->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

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

		$thumbnail = $this->ui->getWidget('thumbnail');
		$thumbnail->image = $this->photo->getUri('thumb', '../');
		$thumbnail->width = $this->photo->getWidth('thumb');
		$thumbnail->height = $this->photo->getHeight('thumb');
		$thumbnail->preview_image = $this->photo->getUri('large', '../');
		$thumbnail->preview_width = $this->photo->getWidth('large');
		$thumbnail->preview_height = $this->photo->getHeight('large');

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
		//$this->navbar->createEntry(Pinhole::_('Edit'));
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

		// sets the date to the set timezone
		$converted_date = $this->photo->photo_date;
		if ($converted_date !== null)
			$converted_date->convertTZbyID($this->photo->photo_time_zone);

		$this->ui->getWidget('photo_date')->value = $converted_date;

	}

	// }}}
}

?>
