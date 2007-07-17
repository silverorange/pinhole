<?php

require_once 'SwatDB/SwatDBClassMap.php';
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
	// {{{ public static function loadSetFromDBWithDimension()

	/**
	 * Load a set of photos for a specified dimension
	 *
	 * A convenience method for efficiently loading a set of {@link
	 * PinholePhoto} dataobjects with all commonly used sub-dataobjects
	 * pre-loaded.
	 *
	 * @param MDB2_Driver_Common $db
	 * @param string $dimension_shortname
	 * @param string $where_clause
	 * @param string $join_clause
	 * @param string $order_by_clause
	 * @param integer $limit
	 * @param integer $offset
	 * @return PinholePhotoWrapper A collection of PinholePhoto dataobjects
	 * 	with pre-loaded sub-dataobjects.
	 */
	public static function loadSetFromDBWithDimension($db, $dimension_shortname,
		$where_clause = '1 = 1', $join_clause = '', $order_by_clause = null,
		$limit = null, $offset = 0)
	{
		if ($order_by_clause === null)
			$order_by_clause = 'PinholePhoto.publish_date desc,
				PinholePhoto.title';

		$sql = 'select PinholePhoto.id,
				PinholePhoto.filename,
				PinholePhoto.title,
				PinholePhoto.photo_date,
				PinholePhotoDimensionBinding.width,
				PinholePhotoDimensionBinding.height,
				PinholeDimension.max_width,
				PinholeDimension.max_height,
				PinholeDimension.shortname,
				PinholeDimension.publicly_accessible
			from PinholePhoto
			%s
			inner join PinholePhotoDimensionBinding on
				PinholePhotoDimensionBinding.photo = PinholePhoto.id
			inner join PinholeDimension on
				PinholePhotoDimensionBinding.dimension = PinholeDimension.id
			where %s
			order by %s';

		$where_clause.= sprintf(' and PinholeDimension.shortname = %s',
			$db->quote($dimension_shortname, 'text'));

		if ($limit !== null)
			$db->setLimit($limit, $offset);

		$rs = SwatDB::query($db, sprintf($sql, $join_clause,
			$where_clause, $order_by_clause), null);

		$store = new SwatDBDefaultRecordsetWrapper(null);

		$photo_class =
			SwatDBClassMap::get('PinholePhoto');
		$dimension_class =
			SwatDBClassMap::get('PinholeDimension');
		$dimension_binding_class =
			SwatDBClassMap::get('PinholePhotoDimensionBinding');

		while ($row = $rs->fetchRow(MDB2_FETCHMODE_OBJECT)) {
			$photo = new $photo_class($row, true);
			$photo->setDataBase($db);

			$dimension = new $dimension_class($row, true);
			$dimension_binding = new $dimension_binding_class($row, true);
			$dimension_binding->dimension = $dimension;

			$photo->setDimension($dimension_shortname, $dimension_binding);

			$store->add($photo);
		}

		return $store;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class = SwatDBClassMap::get('PinholePhoto');
	}

	// }}}
}

?>
