<?php

require_once 'PinholePhotoWrapper.php';

/**
 * A recordset wrapper class for PinholePhoto objects that pre-loads only the
 * thumbnail dimension for efficiency
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholePhoto
 */
class PinholePhotoThumbnailWrapper extends PinholePhotoWrapper
{
	// {{{ protected function getDimensionQuery()

	protected function getDimensionQuery($image_ids)
	{
		$sql = sprintf('select %1$s.*
			from %1$s
			inner join ImageDimension on ImageDimension.id =
				%1$s.dimension
			where %1$s.%2$s in (%3$s) and ImageDimension.shortname = %4$s
			order by %2$s',
			$this->binding_table,
			$this->binding_table_image_field,
			implode(',', $image_ids),
			$this->db->quote('thumbnail', 'text'));

		return $sql;
	}

	// }}}
}

?>
