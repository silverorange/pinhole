<?php

require_once 'Admin/pages/AdminDBOrder.php';
require_once 'Admin/AdminUI.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Pinhole/tags/PinholeTag.php';
require_once 'Pinhole/PinholeTagList.php';
require_once 'Swat/SwatImageDisplay.php';

/**
 * Order page for PinholePhotos
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeTagPhotoOrder extends AdminDBOrder
{
	// {{{ private properties

	private $tag;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->id = SiteApplication::initVar('id');
		$this->initTag();
	}

	// }}}
	// {{{ private function initTag()

	private function initTag()
	{
		$class_name = SwatDBClassMap::get('PinholeTag');
		$this->tag = new $class_name();
		$this->tag->setDatabase($this->app->db);
		$this->tag->setInstance($this->app->getInstance());

		if (!$this->tag->load($this->id))
			throw new AdminNotFoundException(
				sprintf(Pinhole::_('Tag with id “%s” not found.'), $this->id));
	}

	// }}}

	// process phase
	// {{{ protected function saveIndex()

	protected function saveIndex($id, $index)
	{
		$sql = sprintf('update PinholePhotoTagBinding set displayorder = %s
			where tag = %s and photo = %s',
			$this->app->db->quote($index, 'integer'),
			$this->app->db->quote($this->tag->id, 'integer'),
			$this->app->db->quote($id, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()
	protected function buildInternal()
	{
		parent::buildInternal();

		$frame = $this->ui->getWidget('order_frame');
		$frame->title = Admin::_('Order Photos');

		$form = $this->ui->getWidget('order_form');
		$form->action = sprintf('%s/PhotoOrder?id=%s',
			$this->getComponentName(), $this->tag->id);
	}

	// }}}
	// {{{ protected function loadData()

	protected function loadData()
	{
		$tag_list = new PinholeTagList($this->app->db,
			$this->app->getInstance(), $this->tag->name);

		$photos = $tag_list->getPhotos();

		$order_array = array();

		$class_name = SwatDBClassMap::get('PinholeImageSet');
		$set = new $class_name();
		$set->setDatabase($this->app->db);
		$set->instance = $this->app->getInstance();
		$set->loadByShortname('photos');
		$thumb = $set->getDimensionByShortname('thumb');

		foreach ($photos as $photo) {
			$image = new SwatImageDisplay();
			$image->image = $photo->getUri('thumb', '../');
			$image->width = $photo->getWidth('thumb');
			$image->height = $photo->getHeight('thumb');
			$image->occupy_width = $thumb->max_width;
			$image->occupy_height = $thumb->max_height;
			ob_start();
			$image->display();
			$order_array[$photo->id] = ob_get_clean();
		}

		$order_widget = $this->ui->getWidget('order');
		$order_widget->width = '580px';
		$order_widget->height = '400px';
		$order_widget->addOptionsByArray($order_array, 'text/xml');

		$sql = sprintf('select sum(displayorder) from PinholePhotoTagBinding
			where tag = %s',
			$this->app->db->quote($this->tag->id, 'integer'));

		$sum = SwatDB::queryOne($this->app->db, $sql, 'integer');
		$options_list = $this->ui->getWidget('options');
		$options_list->value = ($sum == 0) ? 'auto' : 'custom';
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/admin/styles/pinhole-tag-photo-order.css',
			Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
