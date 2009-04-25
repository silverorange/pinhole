<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatTableStore.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'include/PinholePhotoActionsProcessor.php';
require_once 'Pinhole/admin/PinholePhotoTagEntry.php';

/**
 * Process photos page
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoProcess extends AdminPage
{
	// {{{ protected properties

	protected $ui_xml = 'Pinhole/admin/components/Photo/process.xml';
	protected $unprocessed_photos;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);

		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('select PinholePhoto.id from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where PinholePhoto.status = %s and ImageSet.instance %s %s
			order by PinholePhoto.upload_date desc, PinholePhoto.id',
			$this->app->db->quote(PinholePhoto::STATUS_UNPROCESSED, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$this->unprocessed_photos = SwatDB::query($this->app->db, $sql);

		if (count($this->unprocessed_photos) == 0)
			$this->app->relocate('Photo/Pending');
	}

	// }}}

	// build phase
	// {{{ protected function display()

	protected function display()
	{
		parent::display();
		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->ui->getWidget('processing_message')->content =
			sprintf(Pinhole::ngettext(
				count($this->unprocessed_photos),
				'You have one photo waiting to be processed',
				'You have %s photos waiting to be processed'),
				count($this->unprocessed_photos));
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		$unprocessed_photos_array = array();
		foreach ($this->unprocessed_photos as $photo)
			$unprocessed_photos_array[] = $photo->id;

		return sprintf('var page = new Pinhole.page.ProcessPhotosPage([%s]);',
			implode(', ', $unprocessed_photos_array));
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$yui = new SwatYUI(array('dom', 'animation', 'event', 'connection',
			'json'));

		$this->layout->addHtmlHeadEntrySet($yui->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatJavaScriptHtmlHeadEntry(
			'packages/pinhole/admin/javascript/pinhole-photo-process-page.js',
			Pinhole::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/admin/styles/pinhole-photo-process-page.css',
			Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
