<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatDate.php';
require_once 'Pinhole/tags/PinholeTag.php';

/**
 * Edit page for tags
 *
 * @package   Pinhole
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeTagEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $ui_xml = 'Pinhole/admin/components/Tag/edit.xml';

	/**
	 * @var PinholeTag
	 */
	protected $tag;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
		$this->initTag();
	}

	// }}}
	// {{{ protected function initTag()

	protected function initTag()
	{
		$this->tag = new PinholeTag();
		$this->tag->setDatabase($this->app->db);

		if ($this->id !== null && !$this->tag->load($this->id)) {
			throw new AdminNotFoundException(
				sprintf(Pinhole::_('Tag with id “%s” not found.'),
					$this->id));
		}
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$name = $this->ui->getWidget('name')->value;

		if ($this->id === null && $name === null) {
			$name = $this->generateShortname(
				$this->ui->getWidget('title')->value);

			$this->ui->getWidget('name')->value = $name;

		} elseif (!$this->validateShortname($name)) {
			$message = new SwatMessage(
				Pinhole::_('Tag name already exists and must be unique.'), 
				SwatMessage::ERROR);

			$this->ui->getWidget('name')->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function validateShortname()

	protected function validateShortname($name)
	{
		$sql = 'select name from PinholeTag
			where name = %s and id %s %s';

		$sql = sprintf($sql,
			$this->app->db->quote($name, 'text'),
			SwatDB::equalityOperator($this->id, true),
			$this->app->db->quote($this->id, 'integer'));

		$query = SwatDB::query($this->app->db, $sql);

		return (count($query) == 0);
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array(
			'title',
			'name',
		));

		$this->tag->title = $values['title'];
		$this->tag->name  = $values['name'];

		if ($this->id === null) {
			$now = new SwatDate();
			$this->tag->createdate = $now->getDate();
			$this->tag->parent =
				$this->ui->getWidget('edit_form')->getHiddenField('parent');
		}

		$this->tag->save();

		$message = new SwatMessage(sprintf(
			Pinhole::_('“%s” has been saved.'),
			$this->tag->title));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->tag));
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$edit = $this->navbar->popEntry();
		$this->navbar->addEntry(new SwatNavBarEntry($this->tag->title,
			'Tag/Details?id='.$this->id));

		$this->navbar->addEntry($edit);
	}

	// }}}
}

?>
