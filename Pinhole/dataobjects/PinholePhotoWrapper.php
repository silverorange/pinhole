<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'PinholePhoto.php';

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
				PinholeDimension.max_height
			from PinholePhoto
			inner join PinholePhotoDimensionBinding on
				PinholePhotoDimensionBinding.photo = PinholePhoto.id
			inner join PinholeDimension on
				PinholePhotoDimensionBinding.dimension = PinholeDimension.id
			where %s
			order by PinholePhoto.publish_date desc, PinholePhoto.title
			%s';

		$where_clause.= sprintf(' and PinholeDimension.shortname = %s',
			$db->quote($dimension_shortname, 'text'));

		$set = '';

		if ($limit !== null)
			$set.= sprintf(' limit %d', $db->quote($limit), 'integer');

		if ($offset !== null)
			$set.= sprintf(' offset %d', $db-quote($offset, 'integer'));

		$sql = sprintf($sql,
			$where_clause,
			$set);


		// TODO: use a classmap
		$photos = SwatDB::query($db, $sql, 'PinholePhotoWrapper');

		// TODO: do we want to add a reference to the dimension from
		// the photo?
		/*
		if ($photos !== null)
			$photos->setDimension($);
		*/

		return $photos;
	}

	// }}}
}

?>
