<?php

/**
 * Page for managing a photo's comments
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoComments extends AdminIndex
{
	// {{{ protected properties

	protected $ui_xml = __DIR__.'/comments.xml';

	/**
	 * @var PinholePhoto
	 */
	protected $photo;

	// }}}

	// init phase
	// {{{ protected function initInternal()

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

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatView $view, SwatActions $actions)
	{
		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Comment/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			$this->app->getPage()->setPhoto($this->photo);
			break;
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

		$toolbar = $this->ui->getWidget('comments_toolbar');
		$toolbar->setToolLinkValues($this->photo->id);

		// set default time zone
		$date_column =
			$this->ui->getWidget('comments_view')->getColumn('createdate');

		$date_renderer = $date_column->getRendererByPosition();
		$date_renderer->display_time_zone = $this->app->default_time_zone;
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = sprintf(
			'select id, fullname, photographer, bodytext, createdate, status
			from PinholeComment
			where photo = %s and spam = %s
			order by %s',
			$this->app->db->quote($this->photo->id, 'integer'),
			$this->app->db->quote(false, 'boolean'),
			$this->getOrderByClause($view, 'createdate'));

		$comments = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('PinholeCommentWrapper'));

		$store = new SwatTableStore();

		foreach ($comments as $comment) {
			$ds = new SwatDetailsStore($comment);

			if ($comment->photographer !== null)
				$ds->fullname = $comment->photographer->fullname;

			$ds->bodytext = SwatString::condense(
				SwatString::ellipsizeRight($comment->bodytext, 500));

			$ds->photo_id = $this->photo->id;

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
