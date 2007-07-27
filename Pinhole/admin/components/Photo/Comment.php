<?php

require_once 'Admin/AdminUI.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Pinhole/dataobjects/PinholeCommentWrapper.php';

/**
 * Index page for managing photo comments
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoComment extends AdminIndex
{
	// {{{ protected properties

	protected $id;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->ui->loadFromXML(dirname(__FILE__).'/comment.xml');
		
		$this->id = SiteApplication::initVar('id');

		if (is_numeric($this->id))
			$this->id = intval($this->id);
		}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		$num = count($view->getSelection());
		$message = null;
		
		switch ($actions->selected->id) {
			case 'delete':
				$this->app->replacePage('Photo/CommentDelete');
				$this->app->getPage()->setItems($view->getSelection());
				break;

		case 'show':
			SwatDB::updateColumn($this->app->db, 'PinholeComment',
				'boolean:show', true, 'id', $view->getSelection());

			$message = new SwatMessage(sprintf(Admin::ngettext(
				'One comment has been shown.',
				'%d comments have been shown.', $num),
				SwatString::numberFormat($num)));

			break;

		case 'hide':
			SwatDB::updateColumn($this->app->db, 'PinholeComment',
				'boolean:show', false, 'id', $view->getSelection());

			$message = new SwatMessage(sprintf(Admin::ngettext(
				'One comment has been hidden.',
				'%d comments have been hidden.', $num),
				SwatString::numberFormat($num)));

			break;

		}
		
		if ($message !== null)
			$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		
		$page_title = $this->ui->getWidget('index_frame');
		$page_title->title = sprintf('Comments for photo %s', $this->id);

		// set default time zone
		$date_column =
			$this->ui->getWidget('index_view')->getColumn('createdate');

		$date_renderer = $date_column->getRendererByPosition();
		$date_renderer->display_time_zone = $this->app->default_time_zone;
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = sprintf('select * from PinholeComment where 
			photo = %s order by %s', $this->id, 'createdate');

		$comments = SwatDB::query($this->app->db, $sql, 'PinholeCommentWrapper');

		return $comments;
	}

	// }}}

}

?>
