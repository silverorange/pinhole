<?php

/**
 * A recordset wrapper class for PinholePhoto objects
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholePhotoMetaDataBinding
 */
class PinholePhotoMetaDataBindingWrapper extends SwatDBRecordsetWrapper
{
	// {{{ public static function loadSetFromDB()

	/**
	 * Loads the meta data for a specified photo
	 *
	 * @param integer $photo_id
	 */
	public static function loadSetFromDB($db, $photo_id)
	{
		$sql = 'select PinholePhotoMetaDataBinding.*,
				PinholeMetaData.title,
				PinholeMetaData.shortname,
				PinholeMetaData.machine_tag
			from PinholePhotoMetaDataBinding
			inner join PinholeMetaData on
				PinholeMetaData.id = PinholePhotoMetaDataBinding.meta_data
			where PinholeMetaData.visible = %s
				and PinholePhotoMetaDataBinding.photo = %s
			order by PinholeMetaData.displayorder, PinholeMetaData.title';

		$sql = sprintf($sql,
			$db->quote(true, 'boolean'),
			$db->quote($photo_id, 'integer'));

		$meta_data = SwatDB::query($db, $sql,
			SwatDBClassMap::get('PinholePhotoMetaDataBindingWrapper'));

		return $meta_data;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			SwatDBClassMap::get('PinholePhotoMetaDataBinding');
	}

	// }}}
}

?>
