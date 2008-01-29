<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatDetailsViewField.php';
require_once 'Swat/SwatTextCellRenderer.php';
require_once 'Swat/SwatLinkCellRenderer.php';
require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatString.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/pages/PinholeBrowserPage.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeBrowserDetailsPage extends PinholeBrowserPage
{
	// {{{ protected properties

	/**
	 * @var PinholePhoto
	 */
	protected $photo;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout,
		$photo_id = null, $tags = '')
	{
		parent::__construct($app, $layout, $tags);
		$this->ui_xml = 'Pinhole/pages/browser-details.xml';

		$this->createPhoto($photo_id);
	}

	// }}}
	// {{{ protected function createPhoto()

	protected function createPhoto($photo_id)
	{
		$photo_id = intval($photo_id);
		$photo_class = SwatDBClassMap::get('PinholePhoto');
		$this->photo = new $photo_class();
		$this->photo->setDatabase($this->app->db);
		if ($this->photo->load($photo_id)) {
			// ensure we are loading a photo in the current site instance
			$current_instance_id = $this->app->instance->getId();

			if ($current_instance_id === null)
				$photo_instance_id = $this->photo->image_set->instance;
			else
				$photo_instance_id = $this->photo->image_set->instance->id;

			if ($photo_instance_id != $current_instance_id) {
				// TODO: make exception nicer when instance is null
				throw new SiteNotFoundException(sprintf(
					'Photo does not belong to the current instance: %s.',
					$current_instance->shortname));
			}
		} else {
			// photo was not found
			throw new SiteNotFoundException(sprintf(
				'No photo with the id %d exists.', $photo_id));
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		if (isset($this->layout->navbar)) {
			if (strlen($this->photo->title) > 0)
				$this->layout->navbar->createEntry($this->photo->title);
			else
				$this->layout->navbar->createEntry('Photo');
		}

		// Set YUI Grid CSS class for one full-width column on details page.
		$this->layout->data->yui_grid_class = 'yui-t7';

		// Set photo title.
		if (strlen($this->photo->title) > 0)
			$this->layout->data->title =
				SwatString::minimizeEntities($this->photo->title);
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$view = $this->ui->getWidget('photo_details_view');
		$view->data = $this->getPhotoDetailsStore();

		$this->buildMetaData();
		$this->buildPhotoNextPrev();

		$description = $this->ui->getWidget('description');

		// Set to text/xml for now pending review in ticket #1159.
		$description->content_type = 'text/xml';
		$description->content = $this->photo->description;
	}

	// }}}
	// {{{ protected function buildMetaData()

	protected function buildMetaData()
	{
		$view = $this->ui->getWidget('photo_details_view');

		foreach ($this->photo->meta_data as $meta_data) {
			$field = new SwatDetailsViewField();
			$field->title = $meta_data->title;

			if ($meta_data->machine_tag) {
				if (count($this->getPath()) > 0)
					$base = $this->getPath().'/tag';
				else
					$base = 'tag';

				$renderer = new SwatLinkCellRenderer();
				$renderer->link = sprintf('%s?%s',
					$base, $meta_data->getURI());
			} else {
				$renderer = new SwatTextCellRenderer();
			}

			$renderer->text = $meta_data->value;

			$view->appendField($field);
			$field->addRenderer($renderer);
		}

	}

	// }}}
	// {{{ protected function buildPhotoNextPrev()

	protected function buildPhotoNextPrev()
	{
		$photo_next_prev = $this->ui->getWidget('photo_next_prev');
		$photo_next_prev->setPhoto($this->photo);
		$photo_next_prev->setTagList($this->tag_list);

		if (count($this->getPath()) > 0)
			$photo_next_prev->base = $this->getPath().'/';
	}

	// }}}
	// {{{ protected function getPhotoDetailsStore()

	protected function getPhotoDetailsStore()
	{
		return new SwatDetailsStore($this->photo);
	}

	// }}}
	// {{{ protected function getSubTagList()

	protected function getSubTagList()
	{
		return parent::getSubTagList()->intersect($this->photo->tags);
	}

	// }}}
	// {{{ protected function displayPhoto()

	protected function displayPhoto()
	{
		$img_tag = $this->photo->getImgTag('large');
		$img_tag->class = 'pinhole-photo';
		$img_tag->display();
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$this->displayPhoto();
		parent::displayContent();
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/styles/pinhole-browser-details-page.css',
			Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
