<?php

require_once 'Swat/SwatString.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/exceptions/AdminNoAccessException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Pinhole/dataobjects/PinholePhotographer.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Pinhole/dataobjects/PinholeTagWrapper.php';
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

		$sql = sprintf('select * from PinholeTag
			where status = %s order by title',
			$this->app->db->quote(PinholeTag::STATUS_ENABLED, 'integer'));

		$tags = SwatDB::query($this->app->db, $sql, 'PinholeTagWrapper');

		$this->ui->getWidget('tags')->tags = $tags;

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

		$sql = sprintf('select id, title
			from PinholePhoto where status = %s
			order by PinholePhoto.upload_date, PinholePhoto.id',
			$this->app->db->quote(PinholePhoto::STATUS_PENDING,
				'integer'));

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

		$tags = $this->ui->getWidget('tags')->values;
		$tag_ids = array();
		foreach ($tags as $tag)
			$tag_ids[] = $tag->id;

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

		$image = $this->ui->getWidget('image');
		$dimension = $this->photo->getDimension('large');
		$image->image = '../'.$dimension->getUri();
		$image->width = $dimension->width;
		$image->height = $dimension->height;

		$thumbnail = $this->ui->getWidget('thumbnail');
		$dimension = $this->photo->getDimension('thumb');
		$thumbnail->image = '../'.$dimension->getUri();
		$thumbnail->width = $dimension->width;
		$thumbnail->height = $dimension->height;

		$this->ui->getWidget('details_page')->title = 
			($this->photo->title == null) ?
			Pinhole::_('Photo Edit') :
			SwatString::condense($this->photo->title, 60);

		if ($this->ui->getWidget('edit_form')->hasMessage() ||
			$this->photo->status === PinholePhoto::STATUS_PENDING)
			$this->ui->getWidget('notebook')->selected_page = 'edit_frame';
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->photo));
		$this->ui->getWidget('tags')->values = $this->photo->tags;
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
}

?>
