<?php

require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatDate.php';
require_once 'Pinhole/dataobjects/PinholeTag.php';

/**
 * Edit page for Tags
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

		if ($this->id === null) {
			$parent_id = SiteApplication::initVar('parent');
			$parent_tag = new PinholeTag();
			$parent_tag->setDatabase($this->app->db);
			$parent_tag->load($parent_id);
			$this->tag->parent = $parent_tag;
		} else {
			if (!$this->tag->load($this->id))
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
		$shortname = $this->ui->getWidget('shortname')->value;

		if ($this->id === null && $shortname === null) {
			$shortname = $this->generateShortname(
				$this->ui->getWidget('title')->value);

			$this->ui->getWidget('shortname')->value = $shortname;

		} elseif (!$this->validateShortname($shortname)) {
			$message = new SwatMessage(
				Pinhole::_('Shortname already exists and must be unique.'), 
				SwatMessage::ERROR);

			$this->ui->getWidget('shortname')->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function validateShortname()

	protected function validateShortname($shortname)
	{
		$sql = 'select shortname from PinholeTag
			where shortname = %s and parent %s %s and id %s %s';

		$parent_id = ($this->tag->parent === null) ? null : $this->tag->parent->id;

		$sql = sprintf($sql,
			$this->app->db->quote($shortname, 'text'),
			SwatDB::equalityOperator($parent_id, false),
			$this->app->db->quote($parent_id, 'integer'),
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
			'shortname',
			'status',
		));

		$this->tag->title     = $values['title'];
		$this->tag->shortname = $values['shortname'];
		$this->tag->status    = $values['status'];

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
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$form = $this->ui->getWidget('edit_form');

		if ($this->tag->parent !== null)
			$form->addHiddenField('parent', $this->tag->parent->id);

		$this->ui->getWidget('status')->addOptionsByArray(
			PinholeTag::getStatuses());
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->tag));
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		if ($this->tag->parent !== null || $this->id !== null) {
			$id = ($this->id !== null) ? $this->id : $this->tag->parent->id;

			$navbar_rs = SwatDB::executeStoredProc($this->app->db,
				'getPinholeTagNavBar', array($id));

			foreach ($navbar_rs as $elem)
				$this->navbar->addEntry(new SwatNavBarEntry($elem->title,
					'Tag/Details?id='.$elem->id));
		}

		parent::buildNavBar();
	}

	// }}}
}
?>
