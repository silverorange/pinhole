<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/exceptions/AdminNoAccessException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Pinhole/dataobjects/PinholePhotographer.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'Pinhole/dataobjects/PinholeTagWrapper.php';
require_once 'Pinhole/admin/components/Photo/include/PinholePhotoTagEntry.php';

/**
 * Page for editing a photo
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

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);
		$this->initPhoto();

		$sql = sprintf('select * from PinholeTag
			where status = %s order by title',
			$this->app->db->quote(PinholeTag::STATUS_ENABLED, 'integer'));

		$tags = SwatDB::query($this->app->db, $sql, 'PinholeTagWrapper');

		$this->ui->getWidget('tags')->tags = $tags;
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

		$this->ui->getWidget('tags')->values = $this->photo->tags;
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
		));

		$this->photo->title     = $values['title'];
		$this->photo->description  = $values['description'];
		$this->photo->photo_date = $values['photo_date'];
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

		$this->ui->getWidget('details_page')->title = 
			($this->photo->title === null) ?
			Pinhole::_('Photo Details') :
			SwatString::condense($this->photo->title, 60);

		if ($this->ui->getWidget('edit_form')->hasMessage())
			$this->ui->getWidget('notebook')->selected_page = 'edit_frame';
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->photo));
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
		$this->navbar->createEntry(Pinhole::_('Details'));
	}

	// }}}
}

?>
