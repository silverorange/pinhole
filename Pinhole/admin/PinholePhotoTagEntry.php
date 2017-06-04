<?php

require_once 'Site/SiteTagEntry.php';
require_once 'Pinhole/dataobjects/PinholeTagDataObjectWrapper.php';
require_once 'Pinhole/tags/PinholeTag.php';

/**
 * Control for selecting multiple tags from a list of tags
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoTagEntry extends SiteTagEntry
{
	// {{{ public properties

	public $show_archived_tags = false;

	// }}}
	// {{{ private properties

	/**
	 * Application used by this tag entry control
	 *
	 * @var SiteWebApplication
	 */
	private $app;

	// }}}
	// {{{ public function setAllTags()

	public function setAllTags()
	{
		$instance_id = $this->app->getInstanceId();
		$tag_array = array();
		$where_clause = '';

		if (!$this->show_archived_tags) {
			$where_clause.= sprintf(' and archived = %s',
				$this->app->db->quote(false, 'boolean'));
		}

		$sql = sprintf('select * from PinholeTag
			where instance %s %s %s
			order by title',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$where_clause);

		$tags = SwatDB::query($this->app->db, $sql,
			'PinholeTagDataObjectWrapper');

		// switch to using the JSON server if there are more than 300 tags
		if (count($tags) > 300) {
			$this->json_server = 'Photo/TagServer';
		} else {
			foreach ($tags as $tag)
				$tag_array[$tag->name] = $tag->title;

			$this->setTagArray($tag_array);
		}
	}

	// }}}
	// {{{ public function setApplication()

	/**
	 * Sets the application used by this tag entry control
	 *
	 * @param SiteWebApplication $app the application to use.
	 */
	public function setApplication(SiteWebApplication $app)
	{
		$this->app = $app;
	}

	// }}}
	// {{{ protected function insertTag()

	/**
	 * Creates a new tag
	 *
	 * @throws SwatException if no database connection is set on this tag
	 *                        entry control.
	 */
	protected function insertTag($title, $index)
	{
		if ($this->app === null)
			throw new SwatException(
				'An application must be set on the tag entry control during '.
				'the widget init phase.');

		// check to see if the tag already exists
		$instance_id = $this->app->getInstanceId();
		$sql = sprintf('select * from
			PinholeTag where lower(title) = lower(%s)
				and instance %s %s',
			$this->app->db->quote($title, 'text'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$tags = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('PinholeTagDataObjectWrapper'));

		// only insert if no tag already exists (prevents creating two tags on
		// reloading)
		if (count($tags) > 0) {
			$tag_obj = $tags->getFirst();
		} else {
			$tag_obj = new PinholeTagDataObject();
			$tag_obj->setDatabase($this->app->db);
			$tag_obj->instance = $instance_id;
			$tag_obj->title = $title;
			$tag_obj->save();
			$message = new SwatMessage(
				sprintf(Pinhole::_('“%s” tag has been added'),
					SwatString::minimizeEntities($tag_obj->title)));

			$message->content_type = 'text/xml';
			$message->secondary_content = sprintf(Pinhole::_(
				'You can <a href="Tag/Edit?id=%d">edit this tag</a> '.
				'to customize it.'),
				$tag_obj->id);

			$this->app->messages->add($message);
		}

		$this->tag_array[$tag_obj->name] = $tag_obj->title;
		$this->selected_tag_array[$tag_obj->name] = $tag_obj->title;
	}

	// }}}
}

?>
