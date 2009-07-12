<?php

require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Admin/pages/AdminXMLRPCServer.php';

/**
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeGeoTagPhotoGpsServer extends AdminXMLRPCServer
{
	// process phase
	// {{{ public function processPhoto()

	/**
	 * Update a photo
	 *
	 * @param array $photo_ids The photo its to update
	 * @param float $latitude Latitude
	 * @param float $longitude Longitude
	 * @param integer $zoom_level Zoom level for storing as a preference
	 *
	 * @return array The photo ids that were updated.
	 */
	public function setPhotoGpsData(array $photo_ids, $latitude, $longitude,
		$zoom_level)
	{
		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('update PinholePhoto set
			gps_latitude = %s, gps_longitude = %s
			where PinholePhoto.id in (select PinholePhoto.id from PinholePhoto
				inner join ImageSet on ImageSet.id = PinholePhoto.image_set
				where ImageSet.instance %s %s and PinholePhoto.id in (%s))',
			$this->app->db->quote($latitude, 'float'),
			$this->app->db->quote($longitude, 'float'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->implodeArray($photo_ids, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		// save preferences
		$this->app->config->pinhole->map_last_latitude = $latitude;
		$this->app->config->pinhole->map_last_longitude = $longitude;
		$this->app->config->pinhole->map_last_zoom_level = $zoom_level;
		$this->app->config->save(array(
			'pinhole.map_last_latitude',
			'pinhole.map_last_longitude',
			'pinhole.map_last_zoom_level',
		));

		// clear cache
		if (isset($this->app->memcache)) {
			$this->app->memcache->flushNs('photos');
		}

		return $photo_ids;
	}

	// }}}
}

?>
