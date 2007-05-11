<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'PinholePhoto.php';
require_once 'PinholePhotoDimensionBinding.php';
require_once 'PinholeDimension.php';

/**
 * A recordset wrapper class for PinholePhoto objects
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholePhoto
 */
class PinholePhotoWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = 'PinholePhoto';
	}

	// }}}
	// {{{ public static function loadSetFromDBWithDimension()

	/**
	 * Loads a set of photos with dimension-specific fields filled in with a
	 * specific dimension
	 *
	 * @param MDB2_Driver_Common $db
	 * @param string $dimension_shortname
	 * @param string $where_clause
	 * @param integer $limit
	 * @param integer $offset
	 */
	public static function loadSetFromDBWithDimension($db, $dimension_shortname,
		$where_clause = '1 = 1', $limit = null, $offset = null)
	{
		$sql = 'select PinholePhoto.*,
				PinholePhotoDimensionBinding.width,
				PinholePhotoDimensionBinding.height,
				PinholeDimension.max_width,
				PinholeDimension.max_height,
				PinholeDimension.shortname
			from PinholePhoto
			inner join PinholePhotoDimensionBinding on
				PinholePhotoDimensionBinding.photo = PinholePhoto.id
			inner join PinholeDimension on
				PinholePhotoDimensionBinding.dimension = PinholeDimension.id
			where %s
			order by PinholePhoto.publish_date desc, PinholePhoto.title
			%s';

		$where_clause.= sprintf(' and PinholeDimension.shortname = %s
				and PinholePhoto.status = %s',
			$db->quote($dimension_shortname, 'text'),
			$db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer'));

		$set = '';

		if ($limit !== null)
			$set.= sprintf(' limit %d', $db->quote($limit), 'integer');

		if ($offset !== null)
			$set.= sprintf(' offset %d', $db-quote($offset, 'integer'));

		$sql = sprintf($sql,
			$where_clause,
			$set);


		$rs = SwatDB::query($db, $sql);

		$store = new SwatDBDefaultRecordsetWrapper(null);

		// TODO: use classmaps for these class names

		foreach ($rs as $row) {
			$photo = new PinholePhoto($row);
			$photo->setDataBase($db);

			$dimension = new PinholeDimension($row);
			$dimension_binding = new PinholePhotoDimensionBinding($row);
			$dimension_binding->dimension = $dimension;

			$photo->setDimension($dimension_shortname, $dimension_binding);

			$store->add($photo);
		}

		return $store;
	}

	// }}}
}

?>
