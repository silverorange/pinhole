<?php

require_once 'Site/admin/components/Comment/Edit.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'Pinhole/dataobjects/PinholePhotographerWrapper.php';

/**
 * Page for editing comments
 *
 * @package   Pinhole
 * @copyright 2008-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeCommentEdit extends SiteCommentEdit
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->photographers = $this->getPhotographers();
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return 'Pinhole/admin/components/Comment/edit.xml';
	}

	// }}}
	// {{{ protected function initComment()

	protected function initComment()
	{
		parent::initComment();

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
		}
	}

	// }}}
	// {{{ protected function getPhotographers()

	protected function getPhotographers()
	{
		if ($this->id === null || $this->comment->photographer !== null) {
			$photographer_id = ($this->comment->photographer === null) ?
				0 : $this->comment->photographer->id;

			$instance_id = $this->app->getInstanceId();
			$sql = sprintf('select * from PinholePhotographer
				where instance %s %s and (status = %s or id = %s)
				order by fullname',
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'),
				$this->app->db->quote(
					PinholePhotographer::STATUS_ENABLED, 'integer'),
				$this->app->db->quote($photographer_id, 'integer'));

			$rs =  SwatDB::query($this->app->db, $sql);

			$photographers = array();
			foreach ($rs as $photographer)
				$photographers[$photographer->id] = $photographer->fullname;

		} else {
			return array();
		}
	}

	// }}}
	// {{{ protected function getComment()

	protected function getComment()
	{
		$class_name = SwatDBClassMap::get('PinholeComment');
		return new $class_name();
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$photographer_id = $this->ui->getWidget('photographer')->value;

		if (count($this->photographers) > 0) {
			$class_name = SwatDBClassMap::get('PinholePhotographer');
			$photographer     = new $class_name();
			$photographer->setDatabase($this->app->db);
			if ($photographer->load(
				$photographer_id, $this->app->getInstance())) {

				$this->comment->photographer = $photographer;
			}
		}

		parent::saveDBData();
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
	// {{{ protected function clearCache()

	protected function clearCache()
	{
		if (isset($this->app->memcache)) {
			$this->app->memcache->flushNS('photos');
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->ui->getWidget('edit_form')->action = sprintf('%s?photo=%d',
			$this->source, $this->comment->photo->id);

		if (count($this->photographers) > 0) {
			$this->ui->getWidget('photographer_field')->visible = true;
			$this->ui->getWidget('fullname_field')->visible = false;
			$this->ui->getWidget('link_field')->visible     = false;
			$this->ui->getWidget('email_field')->visible    = false;
			$this->ui->getWidget('status_field')->visible   = false;

			$this->ui->getWidget('photographer')->addOptionsByArray(
				$photographers);

			if ($this->comment->photographer !== null)
				$this->ui->getWidget('photographer')->value =
					$this->comment->photographer->id;
		}

		$statuses = SiteComment::getStatusArray();
		$this->ui->getWidget('status')->addOptionsByArray($statuses);
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
				$this->comment->photo->getTitle(true),
				sprintf('Photo/Details?id=%s', $this->comment->photo->id)));

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
