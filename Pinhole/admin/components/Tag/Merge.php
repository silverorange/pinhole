<?php

require_once 'Admin/pages/AdminDBConfirmation.php';
require_once 'Pinhole/admin/PinholePhotoTagEntry.php';
require_once 'Pinhole/dataobjects/PinholeTagDataObject.php';

/**
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeTagMerge extends AdminDBConfirmation
{
	// {{{ protected properties

	protected $source_tag;
	protected $tag_entry;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->ui_xml = dirname(__FILE__).'/merge.xml';

		parent::initInternal();

		$id = SiteApplication::initVar('id', null, SiteApplication::VAR_GET);
		if ($id !== null)
			$this->setItems(array($id));

		if (count($this->items) != 1)
			throw new SiteNotFoundException('Only 1 id at a time!');

		foreach ($this->items as $item) {
			$id = $item;
		}

		$this->source_tag = $this->getTag($id);

		$this->ui->getWidget('dst_tag')->setApplication($this->app);
		$this->ui->getWidget('dst_tag')->setAllTags();
	}

	// }}}
	// {{{ protected function getTag()

	protected function getTag($id)
	{
		$class_name = SwatDBClassMap::get('PinholeTagDataObject');
		$tag = new $class_name();
		$tag->setDatabase($this->app->db);
		$tag->load($id);
		if ($tag->id === null)
			throw new SiteNotFoundException('Tag not found');
		elseif ($tag->instance !== null &&
			$tag->instance->id !== $this->app->getInstance()->id)
			throw new SiteNotFoundException('Wrong instance');

		return $tag;
	}

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$tags = $this->ui->getWidget('dst_tag')->getSelectedTagArray();
		$dst_tag_name = key($tags);
		$id = SwatDB::queryOne($this->app->db, sprintf('select id
			from PinholeTag where name = %s',
			$this->app->db->quote($dst_tag_name, 'text')));

		$dst_tag = $this->getTag($id);

		// delete intersection tagged photos
		$sql = sprintf('delete from pinholephototagbinding where tag = %s
			and photo in (select photo from pinholephototagbinding
				where pinholephototagbinding.tag = %s)',
			$this->app->db->quote($this->source_tag->id, 'integer'),
			$this->app->db->quote($dst_tag->id, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		// add source_tagged photos to dst_tagged photos
		$sql = sprintf('insert into pinholephototagbinding (photo, tag)
			select pinholephototagbinding.photo, %s
			from pinholephototagbinding where tag = %s',
			$this->app->db->quote($dst_tag->id, 'integer'),
			$this->app->db->quote($this->source_tag->id, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		// delete source_tag
		$this->source_tag->delete();

		$this->app->messages->add(new SwatMessage(
			sprintf(Pinhole::_('“%s” has been merged'),
			$this->source_tag->title)));
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->ui->getWidget('source_tag')->content = $this->source_tag->title;
	}

	// }}}
}

?>
