<?php

require_once 'Swat/SwatControl.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/PinholeTagList.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeStaticMapView extends SwatControl
{
	// {{{ public properties

	public $base = 'map';
	public $show_title = false;
	public $width = 200;
	public $height = 200;
	public $api_key;

	// }}}
	// {{{ protected properties

	protected $tag_list;

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible || !$this->api_key)
			return;

		if ($this->tag_list === null ||
			$this->tag_list->getGeoTaggedPhotoCount() == 0)
			return;

		parent::display();

		echo '<div class="pinhole-map-link">';

		$a_tag = new SwatHtmlTag('a');
		$a_tag->class = 'pinhole-map-link';
		$a_tag->href = $this->base;
		if (count($this->tag_list) > 0) {
			$a_tag->href.= '?'.$this->tag_list->__toString();
		}

		$a_tag->open();

		$img_tag = new SwatHtmlTag('img');
		$img_tag->width = $this->width;
		$img_tag->height = $this->height;
		$img_tag->src = sprintf('http://maps.google.com/staticmap?'.
			'format=png8&sensor=false&maptype=roadmap'.
			sprintf('&size=%sx%s', $this->width, $this->height).
			sprintf('&center=%s,%s', $this->getCenterLatitude(),
				$this->getCenterLongitude()).
			sprintf('&span=%s,%s', $this->getSpanLatitude(),
				$this->getSpanLongitude()).
			sprintf('&markers=%s', implode('|', $this->getMarkers())).
			sprintf('&key=%s', $this->api_key));

		$img_tag->display();

		if ($this->show_title) {
			$this->displayTitle();
		}

		$a_tag->close();

		echo '</div>';
	}

	// }}}
	// {{{ public function setTagList()

	public function setTagList(PinholeTagList $tag_list)
	{
		$this->tag_list = $tag_list;
	}

	// }}}
	// {{{ protected function displayTitle()

	protected function displayTitle()
	{
		$list = $this->tag_list;

		if ($list->getGeoTaggedPhotoCount() == $list->getPhotoCount()) {
			$message = Pinhole::_('View all of these photos on a map.');
		} else {
			$locale = SwatI18NLocale::get();
			$message = sprintf(Pinhole::_('View %s of these photos on a map.'),
				$locale->formatNumber($list->getGeoTaggedPhotoCount()));
		}

		echo SwatString::minimizeEntities($message);
	}

	// }}}
	// {{{ protected function getMarkers()

	protected function getMarkers()
	{
		// add a random 50 photos
		$tag_list = clone $this->tag_list;
		$tag_list->setPhotoRange(new SwatDBRange(50));
		$tag_list->setShowOnlyGeoTaggedPhotos(true);
		$tag_list->setPhotoOrderByClause('random()');

		$photos = $tag_list->getPhotos(false,
			array('gps_latitude', 'gps_longitude'));

		$markers = array();
		foreach($photos as $photo) {
			$markers[] = sprintf('%s,%s,%s',
				round($photo->gps_latitude, 4),
				round($photo->gps_longitude, 4),
				$this->getMarkerType());
		}

		return $markers;
	}

	// }}}
	// {{{ protected function getMarkerType()

	protected function getMarkerType()
	{
		return 'smallred';
	}

	// }}}
	// {{{ protected function getSpanLatitude()

	protected function getSpanLatitude()
	{
		$info = $this->tag_list->getPhotoInfo();

		return max(0.003, // 0.003 displays a better surrounding than 0
			abs($info['max_latitude']  - $this->getCenterLatitude()),
			abs($info['min_latitude']  - $this->getCenterLatitude())
		);
	}

	// }}}
	// {{{ protected function getSpanLongitude()

	protected function getSpanLongitude()
	{
		$info = $this->tag_list->getPhotoInfo();

		return max(0.003, // 0.003 displays a better surrounding than 0
			abs($info['max_longitude'] - $this->getCenterLongitude()),
			abs($info['min_longitude'] - $this->getCenterLongitude())
		);
	}

	// }}}
	// {{{ protected function getCenterLatitude()

	protected function getCenterLatitude()
	{
		$info = $this->tag_list->getPhotoInfo();
		return (($info['max_latitude'] + $info['min_latitude']) / 2);
	}

	// }}}
	// {{{ protected function getCenterLongitude()

	protected function getCenterLongitude()
	{
		$info = $this->tag_list->getPhotoInfo();
		return (($info['max_longitude'] + $info['min_longitude']) / 2);
	}

	// }}}
}

?>
