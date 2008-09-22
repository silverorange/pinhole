<?php

require_once 'Swat/Swat.php';
require_once 'Site/pages/SitePage.php';

/**
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoTagServer extends SitePage
{
	// {{{ public function build()

	public function build()
	{
		header('Content-type: application/json; charset=utf-8');

		$query = $this->app->initVar('query');

		$results = array();
		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('select title, name from PinholeTag
			where instance %s %s and (title like %s or name like %s)
			order by title',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote($query.'%', 'text'),
			$this->app->db->quote($query.'%', 'text'));

		$rs = SwatDB::query($this->app->db, $sql, null);

		while ($row = $rs->fetchRow(MDB2_FETCHMODE_OBJECT)) {
			$result = array();
			$result['Title'] = $row->title;
			$result['Shortname'] = $row->name;
			$results[] = $result;
		}

		$encoded = json_encode($results);
		$encoded = substr($encoded, 1, strlen($encoded) - 2);

		echo '{"ResultSet":{"Result" :['.$encoded.']}}';
	}

	// }}}
	// {{{ protected function createLayout()

	protected function createLayout()
	{
		return new SiteLayout($this->app, 'Pinhole/layouts/xhtml/blank.php');
	}

	// }}}

}
