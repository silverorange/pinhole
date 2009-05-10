<?php

require_once 'Swat/SwatDate.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'NateGoSearch/NateGoSearch.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'Pinhole/dataobjects/PinholeComment.php';
require_once 'Pinhole/dataobjects/PinholePhotographerWrapper.php';

/**
 * Page for editing comments
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeCommentEdit extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Pinhole/admin/components/Comment/edit.xml';

	/**
	 * @var PinholeComment
	 */
	protected $comment;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		$this->initPinholeComment();

		$photographers = array();

		if ($this->id === null || $this->comment->photographer !== null) {
			$this->ui->getWidget('photographer_field')->visible = true;

			$instance_id = $this->app->getInstanceId();
			$sql = sprintf('select PinholePhotographer.*
				from PinholePhotographer
				where PinholePhotographer.instance %s %s
				order by fullname',
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'));

			$rs = SwatDB::query($this->app->db, $sql);

			foreach ($rs as $row)
				$photographers[$row->id] = $row->fullname;

			$this->ui->getWidget('photographer')->addOptionsByArray(
				$photographers);
		}

		if (count($photographers) > 0) {
			$this->ui->getWidget('fullname_field')->visible = false;
			$this->ui->getWidget('link_field')->visible     = false;
			$this->ui->getWidget('email_field')->visible    = false;
			$this->ui->getWidget('status_field')->visible   = false;
		} else {
			$this->ui->getWidget('photographer_field')->visible = false;
		}
	}

	// }}}
	// {{{ protected function initPinholeComment()

	protected function initPinholeComment()
	{
		$class_name = SwatDBClassMap::get('PinholeComment');
		$this->comment = new $class_name();
		$this->comment->setDatabase($this->app->db);

		if ($this->id === null) {
			$photo_id = $this->app->initVar('photo');
			$class_name = SwatDBClassMap::get('PinholePhoto');
			$photo = new $class_name();
			$photo->setDatabase($this->app->db);

			if ($photo_id === null) {
				throw new AdminNotFoundException(
					'Photo must be specified when creating a new comment.');
			}

			if (!$photo->load($photo_id, $this->app->getInstance())) {
				throw new AdminNotFoundException(
					sprintf('Photo with id ‘%s’ not found.', $photo_id));
			}

			$this->comment->photo = $photo;

		} elseif (!$this->comment->load($this->id, $this->app->getInstance())) {
			throw new AdminNotFoundException(
				sprintf('Comment with id ‘%s’ not found.', $this->id));
		}
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array(
			'fullname',
			'link',
			'email',
			'bodytext',
			'status',
			'photographer',
		));

		if ($this->comment->id === null) {
			$now = new SwatDate();
			$now->toUTC();
			$this->comment->createdate   = $now;
		}

		if ($this->ui->getWidget('photographer_field')->visible) {
			$photographer_id  = $values['photographer'];
			$class_name = SwatDBClassMap::get('PinholePhotographer');
			$photographer     = new $class_name();
			$photographer->setDatabase($this->app->db);
			if ($photographer->load($photographer_id, $this->app->getInstance())) {
				$this->comment->photographer = $photographer;
			}
		} else {
			$this->comment->fullname = $values['fullname'];
			$this->comment->link     = $values['link'];
			$this->comment->email    = $values['email'];
			$this->comment->status   = $values['status'];
		}

		if ($this->comment->status === null) {
			$this->comment->status = SiteComment::STATUS_PUBLISHED;
		}

		$this->comment->bodytext = $values['bodytext'];

		if ($this->comment->isModified()) {
			$this->comment->save();

			$this->addToSearchQueue();

			if (isset($this->app->memcache)) {
				$this->app->memcache->flushNS('photos');
			}

			$message = new SwatMessage(Pinhole::_('Comment has been saved.'));

			$this->app->messages->add($message);
		}
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue()
	{
		$this->addPhotoToSearchQueue();
		$this->addCommentToSearchQueue();
	}

	// }}}
	// {{{ protected function addPhotoToSearchQueue()

	protected function addPhotoToSearchQueue()
	{
		// this is automatically wrapped in a transaction because it is
		// called in saveDBData()
		$type = NateGoSearch::getDocumentType($this->app->db, 'photo');

		if ($type === null)
			return;

		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->comment->photo->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->comment->photo->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function addCommentToSearchQueue()

	protected function addCommentToSearchQueue()
	{
		// this is automatically wrapped in a transaction because it is
		// called in saveDBData()
		$type = NateGoSearch::getDocumentType($this->app->db, 'comment');

		if ($type === null)
			return;

		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->comment->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->comment->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$statuses = SiteComment::getStatusArray();
		$this->ui->getWidget('status')->addOptionsByArray($statuses);

		$this->ui->getWidget('edit_form')->action = sprintf('%s?photo=%d',
			$this->source, $this->comment->photo->id);
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->comment));

		if ($this->comment->photographer !== null)
			$this->ui->getWidget('photographer')->value = $this->comment->photographer->id;
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$photo_id = $this->app->initVar('photo');
		if ($photo_id !== null) {
			$this->navbar->popEntry();
			$this->navbar->popEntry();

			$this->navbar->addEntry(new SwatNavBarEntry(
				Pinhole::_('Photos'), 'Photo'));

			$this->navbar->addEntry(new SwatNavBarEntry(
				$this->comment->photo->getTitle(),
				sprintf('Photo/Comments?id=%s', $this->comment->photo->id)));

			if ($this->id === null)
				$this->navbar->addEntry(new SwatNavBarEntry(
					Pinhole::_('New Comment')));
			else
				$this->navbar->addEntry(new SwatNavBarEntry(
					Pinhole::_('Edit Comment')));
		}
	}

	// }}}
}

?>
