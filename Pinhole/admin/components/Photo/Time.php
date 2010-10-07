<?php

require_once 'Admin/pages/AdminDBConfirmation.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'SwatDB/SwatDB.php';
require_once 'HotDate/HotDateTimeZone.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';

/**
 * Page for modifying date/time/time-zone of photos
 *
 * @package   Pinhole
 * @copyright 2009-2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoTime extends AdminDBConfirmation
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		// override default xml
		$this->ui = new AdminUI();
		$this->ui->loadFromXML(dirname(__FILE__).'/time.xml');
	}

	// }}}
	// {{{ protected function getPhotos()

	protected function getPhotos()
	{
		$item_list = $this->getItemList('integer');
		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('select PinholePhoto.* from PinholePhoto
				inner join ImageSet on PinholePhoto.image_set = ImageSet.id
				where ImageSet.instance %s %s',
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'));

		// note the only page with an extended-selection that accesses this
		// is the pending photos page - so enforce status here.
		if ($this->extended_selected) {
			$sql.= sprintf(' and PinholePhoto.status = %s',
				$this->app->db->quote(PinholePhoto::STATUS_PENDING, 'integer'));
		} else {
			$sql.= sprintf(' and PinholePhoto.id in (%s)', $item_list);
		}

		$wrapper_class = SwatDBClassMap::get('PinholePhotoWrapper');
		return SwatDB::query($this->app->db, $sql, $wrapper_class);
	}

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		if ($this->ui->getWidget('photo_time_zone')->value !== null &&
			$this->ui->getWidget('camera_time_zone')->value !== null) {
			$num = $this->adjustTimeZone();
		} else {
			$num = $this->addDateParts();
		}

		$message = new SwatMessage(sprintf(Pinhole::ngettext(
			'One photo has been updated.', '%d photos have been updated.',
			$num), SwatString::numberFormat($num)), SwatMessage::NOTIFICATION);

		$this->app->messages->add($message);

		if (isset($this->app->memcache))
			$this->app->memcache->flushNs('photos');
	}

	// }}}
	// {{{ protected function adjustTimeZone()

	protected function adjustTimeZone()
	{
		$item_list = $this->getItemList('integer');

		$date = new SwatDate();

		$photo_tz = new HotDateTimeZone(
			$this->ui->getWidget('photo_time_zone')->value);

		$camera_tz = new HotDateTimeZone(
			$this->ui->getWidget('camera_time_zone')->value);

		$photo_offset = $photo_tz->getOffset($date);
		$camera_offset = $camera_tz->getOffset($date);

		$offset_s = $photo_offset - $camera_offset;
		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('update PinholePhoto set photo_time_zone = %s,
			photo_date = convertToUTC(convertTZ(photo_date, photo_time_zone)
				+ interval %s, %s)
			where PinholePhoto.image_set in (
				select id from ImageSet where instance %s %s)',
			$this->app->db->quote($photo_tz->getName(), 'text'),
			$this->app->db->quote($offset_s.' seconds', 'text'),
			$this->app->db->quote($photo_tz->getName(), 'text'),
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'));

		// note the only page with an extended-selection that accesses this
		// is the pending photos page - so enforce status here.
		if ($this->extended_selected) {
			$sql.= sprintf(' and PinholePhoto.status = %s',
				$this->app->db->quote(PinholePhoto::STATUS_PENDING, 'integer'));
		} else {
			$sql.= sprintf(' and PinholePhoto.id in (%s)', $item_list);
		}

		return SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function addDateParts()

	protected function addDateParts()
	{
		$item_list = $this->getItemList('integer');

		$date_parts = array('year', 'month', 'day', 'hour', 'minute', 'second');
		$date_parts_sql = 'photo_date = photo_date';
		foreach ($date_parts as $part) {
			$value = $this->ui->getWidget('time_'.$part)->value;
			if ($value !== null) {
				$date_parts_sql.= sprintf('+ interval %s',
					$this->app->db->quote($value.' '.$part, 'text'));
			}
		}

		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('update PinholePhoto set %s
			where PinholePhoto.image_set in (
				select id from ImageSet where instance %s %s)',
			$date_parts_sql,
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		// note the only page with an extended-selection that accesses this
		// is the pending photos page - so enforce status here.
		if ($this->extended_selected) {
			$sql.= sprintf(' and PinholePhoto.status = %s',
				$this->app->db->quote(PinholePhoto::STATUS_PENDING, 'integer'));
		} else {
			$sql.= sprintf(' and PinholePhoto.id in (%s)', $item_list);
		}

		return SwatDB::exec($this->app->db, $sql);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		if ($this->extended_selected) {
			// note the only page with an extended-selection that accesses this
			// is the pending photos page - so the message can be more
			// specific.
			$message = $this->ui->getWidget('confirmation_message');
			$message->content_type = 'text/xml';
			$message->content = Pinhole::_('<strong>Update the date/time/'.
				'time-zone of all pending photos.</strong>');

		} else {
			$this->ui->getWidget('confirmation_frame')->classes[] =
				'two-column';

			$container = $this->ui->getWidget('confirmation_container');
			$photo_view = $this->ui->getWidget('photo_view');

			$store = new SwatTableStore();

			foreach ($this->getPhotos() as $photo) {
				$ds = new SwatDetailsStore();
				$ds->photo = $photo;
				$store->add($ds);
			}

			$photo_view->model = $store;

			$message = $this->ui->getWidget('confirmation_message');
			$message->content_type = 'text/xml';
			$message->content = sprintf(Pinhole::_('<strong>Update the '.
				'date/time/time-zone of the following %s:</strong>'),
				Pinhole::ngettext('photo', 'photos', count($store)));
		}
	}

	// }}}
	// {{{ protected function display()

	protected function display()
	{
		parent::display();
		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		return "new PinholePhotoTimePage();";
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/admin/styles/pinhole-photo-tile.css',
			Pinhole::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/admin/styles/pinhole-photo-time.css',
			Pinhole::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			'packages/pinhole/admin/javascript/pinhole-photo-time.js',
			Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
