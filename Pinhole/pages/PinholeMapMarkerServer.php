<?php

require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatUI.php';
require_once 'Site/pages/SiteXMLRPCServer.php';
require_once 'Site/exceptions/SiteNotFoundException.php';

/**
 * Handles XML-RPC requests from the map page
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeMapMarkerServer extends SiteXMLRPCServer
{
	// {{{ protected properties

	protected $photo_count;

	protected $max_photos = 8;

	// }}}
	// {{{ public function getMarkerContent()

	/**
	 * Returns the XHTML required to display the content of a marker balloon
	 *
	 * @param array $photo_ids Photo Ids
	 * @param string $tag_list Tag list to append to link uri's
	 *
	 * @return string the XHTML required to display the content of a marker
	 *                balloon
	 */
	public function getMarkerContent(array $photo_ids, $tag_list = null)
	{
		$this->photo_count = count($photo_ids);

		if (count($photo_ids) == 0) {
			return Pinhole::_('Error: no matching photos');
		}

		$photos = $this->getPhotos($photo_ids, $tag_list);

		if (count($photos) == 0) {
			return Pinhole::_('Error: no matching photos');
		}

		ob_start();
		echo '<div class="pinhole-map-marker">';

		if (count($photos) == 1) {
			$this->displaySinglePhoto($photos->getFirst(), $tag_list);
		} else {
			$box = $this->getBoundingBox($photo_ids);
			$this->displayPhotoGrid($photos, $tag_list, $box);
		}

		echo '</div>';

		return ob_get_clean();
	}

	// }}}
	// {{{ private function getPhotos()

	private function getPhotos($photo_ids)
	{
		$instance_id = ($this->app->getInstance() === null) ?
			null : $this->app->getInstanceId();

		$sql = sprintf('select PinholePhoto.* from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where PinholePhoto.status = %s and ImageSet.instance %s %s
				and PinholePhoto.id in (%s)
			order by PinholePhoto.publish_date desc',
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->implodeArray($photo_ids, 'integer'));

		$this->app->db->setLimit($this->max_photos);

		return SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('PinholePhotoWrapper'));
	}

	// }}}
	// {{{ private function getBoundingBox()

	private function getBoundingBox($photo_ids)
	{
		// a box is easier for now, but we might have to do a radius
		// to be more accurate

		$instance_id = ($this->app->getInstance() === null) ?
			null : $this->app->getInstanceId();

		$sql = sprintf('select
				max(PinholePhoto.gps_latitude) as max_latitude,
				min(PinholePhoto.gps_latitude) as min_latitude,
				max(PinholePhoto.gps_longitude) as max_longitude,
				min(PinholePhoto.gps_longitude) as min_longitude
			from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where PinholePhoto.status = %s and ImageSet.instance %s %s
				and PinholePhoto.id in (%s)',
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->implodeArray($photo_ids, 'integer'));

		$row = SwatDB::queryRow($this->app->db, $sql);

		return array(
			'max_latitude'  => $row->max_latitude,
			'min_latitude'  => $row->min_latitude,
			'max_longitude' => $row->max_longitude,
			'min_longitude' => $row->min_longitude,
		);
	}

	// }}}
	// {{{ private function displaySinglePhoto()

	private function displaySinglePhoto(PinholePhoto $photo, $tag_list)
	{
		$a_tag = new SwatHtmlTag('a');
		$a_tag->href = sprintf('%sphoto/%s',
			$this->app->config->pinhole->path, $photo->id);

		if ($tag_list !== null)
			$a_tag->href.= '?'.$tag_list;

		$a_tag->open();

		echo $photo->getImgTag('small')->__toString();

		if ($photo->getTitle() != '') {
			$h2_tag = new SwatHtmlTag('div');
			$h2_tag->setContent($photo->getTitle());
			$h2_tag->display();
		}

		$a_tag->close();
	}

	// }}}
	// {{{ private function displayPhotoGrid()

	private function displayPhotoGrid(PinholePhotoWrapper $photos, $tag_list,
		array $box)
	{
		echo '<div class="marker_content">';

		$ui = new SwatUI();
		$ui->loadFromXml(dirname(__FILE__).'/map-tile.xml');

		$store = new SwatTableStore();
		foreach ($photos as $photo) {
			$ds = new SwatDetailsStore();
			$ds->photo = $photo;
			$ds->root_path = $this->app->config->pinhole->path;
			$ds->path = 'photo/'.$photo->id;
			if ($tag_list !== null)
				$ds->path.= '?'.$tag_list;

			$ds->display_title =
				$this->app->config->pinhole->browser_index_titles;

			$store->add($ds);
		}

		$ui->getWidget('photo_view')->model = $store;

		$locale = SwatI18NLocale::get();

		$h3_tag = new SwatHtmlTag('h3');
		$h3_tag->setContent(sprintf(
			Pinhole::_('%s Photos, Displaying %s to %s'),
			$locale->formatNumber($this->photo_count),
			$locale->formatNumber(1),
			$locale->formatNumber(count($photos))));

		$h3_tag->display();

		if (count($photos) > 3) {
			echo '<div class="fixed-width-marker">';
			$ui->display();
			echo '</div>';
		} else {
			$ui->display();
		}

		if (count($photos) < $this->photo_count) {
			$a_tag = new SwatHtmlTag('a');
			$a_tag->class = 'more-photos-link';
			$a_tag->setContent(sprintf(Pinhole::_('View All %s Photos'),
				$locale->formatNumber($this->photo_count)));

			if ($box['min_latitude'] == $box['max_latitude'] &&
				$box['min_longitude'] == $box['max_longitude']) {

				$a_tag->href = sprintf('tag?gps.latitude=%s/gps.longitude=%s',
					$box['min_latitude'], $box['min_longitude']);
			} else {
				$a_tag->href = sprintf(
					'javascript:PinholeMap.loadMarkerContent("%s/gps.box=%sx%s|%sx%s/page.number=2")',
					$tag_list,
					$box['max_latitude'], $box['min_longitude'],
					$box['min_latitude'], $box['max_longitude']);
			}

			$a_tag->display();
		}

		echo '</div>';
	}

	// }}}
}

?>
