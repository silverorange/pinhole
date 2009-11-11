<?php

require_once 'Swat/SwatString.php';
require_once 'Site/SiteCommentStatus.php';
require_once 'Site/admin/SiteCommentStatusSlider.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/exceptions/AdminNoAccessException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'NateGoSearch/NateGoSearch.php';
require_once 'Pinhole/dataobjects/PinholePhotographer.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';
require_once 'Pinhole/PinholeTagList.php';
require_once 'Pinhole/dataobjects/PinholeTagDataObjectWrapper.php';
require_once 'Pinhole/tags/PinholeTag.php';
require_once 'Pinhole/admin/PinholePhotoTagEntry.php';

/**
 * Page for viewing photo details and editing
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $ui_xml = 'Pinhole/admin/components/Photo/edit.xml';

	/**
	 * @var PinholePhoto
	 */
	protected $photo;

	/**
	 * @var boolean
	 */
	protected $pending = false;

	/**
	 * @var array
	 */
	protected $pending_photos = array();

	/**
	 * @var PinholeImageDimensionWrapper
	 */
	protected $dimensions;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		$this->initPhoto();
		$this->initStatuses();
		$this->initCommentStatuses();

		if ($this->photo->status == PinholePhoto::STATUS_PENDING)
			$this->pending = true;

		$this->pending_photos = $this->getUpcomingPhotos();

		$this->ui->getWidget('for_sale_field')->visible =
			($this->app->config->clustershot->username !== null);

		if ($this->app->config->pinhole->enable_private_photos) {
			$this->ui->getWidget('private_field')->visible = true;
			$this->ui->getWidget('passphrase_field')->visible =
				($this->app->config->pinhole->passphrase === null);
		}

		// setup tag entry control
		$this->ui->getWidget('tags')->setApplication($this->app);
		$this->ui->getWidget('tags')->setAllTags();

		$replicator = $this->ui->getWidget('site_link_field');
		$replicators = array();
		$dimensions = $this->getDimensions();
		foreach ($dimensions as $dimension)
			$replicators[$dimension->id] = $dimension->title;

		$replicator->replicators = $replicators;

		$comment_status = $this->app->config->pinhole->global_comment_status;

		$this->ui->getWidget('comment_status_field')->visible =
			($comment_status === null);

		$this->ui->getWidget('comments_link')->visible =
			($comment_status === null || $comment_status == true);

		// this is temp until there's another toolbar option
		$this->ui->getWidget('toolbar')->visible =
			($comment_status === null || $comment_status == true);
	}

	// }}}
	// {{{ protected function initPhoto()

	protected function initPhoto()
	{
		$class_name = SwatDBClassMap::get('PinholePhoto');
		$this->photo = new $class_name();
		$this->photo->setDatabase($this->app->db);

		if ($this->id === null) {
			throw new AdminNoAccessException(
				Pinhole::_('A Photo id is required.'));
		} else {
			$instance_id = $this->app->getInstanceId();

			if (!$this->photo->load($this->id))
				throw new AdminNotFoundException(
					sprintf(Pinhole::_('Photo with id “%s” not found.'),
					$this->id));
			elseif ($this->photo->image_set->instance !== null &&
				$this->photo->image_set->instance->id != $instance_id)
				throw new AdminNotFoundException(
					sprintf(Pinhole::_('Photo with id “%s” loaded '.
						'in the wrong instance.'),
					$this->id));
		}
	}

	// }}}
	// {{{ protected function initStatuses()

	protected function initStatuses()
	{
		$status = $this->ui->getWidget('status');

		if ($this->photo->status == PinholePhoto::STATUS_PENDING)
			$status->addOption(PinholePhoto::STATUS_PENDING,
				sprintf(Pinhole::_('Leave as %s'),
				PinholePhoto::getStatusTitle(PinholePhoto::STATUS_PENDING)));

		$status->addOption(PinholePhoto::STATUS_PUBLISHED,
			PinholePhoto::getStatusTitle(PinholePhoto::STATUS_PUBLISHED));
		$status->addOption(PinholePhoto::STATUS_UNPUBLISHED,
			PinholePhoto::getStatusTitle(PinholePhoto::STATUS_UNPUBLISHED));
	}

	// }}}
	// {{{ protected function initCommentStatuses()

	protected function initCommentStatuses()
	{
		$status = $this->ui->getWidget('comment_status');

		// open
		$option = new SwatOption(SiteCommentStatus::OPEN,
			PinholePhoto::getCommentStatusTitle(SiteCommentStatus::OPEN));

		$status->addOption($option);
		$status->addContextNote($option, Pinhole::_(
			'Comments can be added by anyone and are immediately visible with '.
			'this photo.'));

		// moderated
		$option = new SwatOption(SiteCommentStatus::MODERATED,
			PinholePhoto::getCommentStatusTitle(
				SiteCommentStatus::MODERATED));

		$status->addOption($option);
		$status->addContextNote($option, Pinhole::_(
			'Comments can be added by anyone but must be approved by a site '.
			'photographer before being visible with this photo.'));

		// locked
		$option = new SwatOption(SiteCommentStatus::LOCKED,
			PinholePhoto::getCommentStatusTitle(SiteCommentStatus::LOCKED));

		$status->addOption($option);
		$status->addContextNote($option, Pinhole::_(
			'Comments can only be added by a photographer. Existing comments are '.
			'still visible with this photo.'));

		// closed
		$option = new SwatOption(SiteCommentStatus::CLOSED,
			PinholePhoto::getCommentStatusTitle(SiteCommentStatus::CLOSED));

		$status->addOption($option);
		$status->addContextNote($option, Pinhole::_(
			'Comments can only be added by a photographer. No comments are visible '.
			'with this photo.'));

		if ($this->id === null) {
			switch ($this->app->config->pinhole->default_comment_status) {
			case 'open':
				$status->value = SiteCommentStatus::OPEN;
				break;

			case 'moderated':
				$status->value = SiteCommentStatus::MODERATED;
				break;

			case 'locked':
				$status->value = SiteCommentStatus::LOCKED;
				break;

			case 'closed':
			default:
				$status->value = SiteCommentStatus::CLOSED;
				break;
			}
		}
	}

	// }}}
	// {{{ protected function getUpcomingPhotos()

	protected function getUpcomingPhotos()
	{
		$instance_id = $this->app->getInstanceId();

		if ($this->pending) {
			$status = sprintf('PinholePhoto.status = %s',
				$this->app->db->quote(PinholePhoto::STATUS_PENDING,
				'integer'));

			$order_by = 'PinholePhoto.upload_date, PinholePhoto.id';
		} else {
			$status = '1 = 1';
			$order_by = 'PinholePhoto.publish_date desc,
				PinholePhoto.photo_date asc, PinholePhoto.id';
		}

		$sql = sprintf('select PinholePhoto.id, PinholePhoto.title
			from PinholePhoto
			inner join ImageSet on PinholePhoto.image_set = ImageSet.id
			where %s and ImageSet.instance %s %s
			order by %s',
			$status,
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$order_by);

		return SwatDB::query($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function getUpcomingPhotoCount()

	protected function getUpcomingPhotoCount()
	{
		$count = 0;
		$found = false;
		foreach ($this->pending_photos as $photo) {
			if ($photo->id == $this->photo->id)
				$found = true;
			elseif ($found)
				$count++;
		}

		return $count;
	}

	// }}}
	// {{{ protected function getPendingPhotoCount()

	protected function getPendingPhotoCount()
	{
		return (count($this->pending_photos));
	}

	// }}}
	// {{{ protected function getNextPhoto()

	protected function getNextPhoto()
	{
		$found = false;

		foreach ($this->pending_photos as $photo) {
			if ($photo->id == $this->photo->id)
				$found = true;
			elseif ($found)
				return $photo;
		}

		return false;
	}

	// }}}
	// {{{ protected function getDimensions()

	protected function getDimensions()
	{
		if ($this->dimensions === null) {
			$sql = sprintf('select ImageDimension.*
					from PinholePhotoDimensionBinding
					inner join ImageDimension on
						PinholePhotoDimensionBinding.dimension =
							ImageDimension.id
					where PinholePhotoDimensionBinding.photo = %s
					order by coalesce(ImageDimension.max_width,
						ImageDimension.max_height) asc',
				$this->app->db->quote($this->id, 'integer'));

			$wrapper = SwatDBClassMap::get('PinholeImageDimensionWrapper');
			$this->dimensions = SwatDB::query($this->app->db, $sql, $wrapper);
		}

		return $this->dimensions;
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$private = $this->ui->getWidget('private')->value;

		if ($this->app->config->pinhole->passphrase === null && $private &&
			$this->ui->getWidget('passphrase')->value === null) {

			$message = new SwatMessage(Pinhole::_('A password is required for '.
				'private photos.'));

			$this->ui->getWidget('passphrase')->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->setPhotoValues();

		$this->photo->save();

		$tags = $this->ui->getWidget('tags')->getSelectedTagArray();
		$this->photo->addTagsByName($tags, true);

		if ($this->app->config->pinhole->passphrase === null &&
			$this->ui->getWidget('private')->value) {
			$this->app->config->pinhole->passphrase =
				md5($this->ui->getWidget('passphrase')->value);

			$this->app->config->save(array('pinhole.passphrase'));
		}

		$this->addToSearchQueue();

		if (isset($this->app->memcache))
			$this->app->memcache->flushNs('photos');

		$message = new SwatMessage(sprintf(
			Pinhole::_('“%s” has been saved.'),
			$this->photo->getTitle(true)));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function setPhotoValues()

	protected function setPhotoValues()
	{
		$values = $this->getUIValues();

		// turns the date back into UTC
		$photo_date = new SwatDate($values['photo_date']);
		$photo_date->setTZbyID($this->photo->photo_time_zone);
		$photo_date->toUTC();

		$this->photo->title = $values['title'];
		$this->photo->description = $values['description'];
		$this->photo->photo_date = $photo_date;
		$this->photo->private = $values['private'];
		$this->photo->for_sale = $values['for_sale'];
		$this->photo->photo_time_zone = $values['photo_time_zone'];
		$this->photo->setStatus($values['status']);

		if ($this->ui->getWidget('comment_status_field')->visible)
			$this->photo->comment_status = $values['comment_status'];

		if ($this->photo->photo_time_zone === null) {
			$this->photo->photo_time_zone =
				$this->app->default_time_zone->getID();
		}
	}

	// }}}
	// {{{ protected function getUIValues()

	protected function getUIValues()
	{
		return $this->ui->getValues(array(
			'title',
			'description',
			'photo_date',
			'photo_time_zone',
			'status',
			'private',
			'for_sale',
			'comment_status',
		));
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue()
	{
		$type = NateGoSearch::getDocumentType($this->app->db, 'photo');

		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->photo->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->photo->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		if ($this->ui->getWidget('proceed_button')->hasBeenClicked() &&
			$this->getNextPhoto() !== false)
			$this->app->relocate($this->getComponentName().'/Edit?id='.
				$this->getNextPhoto()->id);
		elseif ($this->pending && $this->getPendingPhotoCount() > 0)
			$this->app->relocate($this->getComponentName().'/Pending');
		elseif ($this->pending)
			$this->app->relocate($this->getComponentName());
		else
			parent::relocate();
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->startCapture('content');
		Swat::displayInlineJavaScript('var edit_page = new PinholePhotoEditPage();');
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->buildPreview();
		$this->buildPendingCount();
		$this->buildSiteLinks();

		$this->ui->getWidget('edit_frame')->title = null;

		$toolbar = $this->ui->getWidget('toolbar');
		$toolbar->setToolLinkValues($this->photo->id);
	}

	// }}}
	// {{{ protected function buildPreview()

	protected function buildPreview()
	{
		$preview = $this->ui->getWidget('preview');
		$preview->width = $this->photo->getWidth('small');
		$preview->height = $this->photo->getHeight('small');
		$preview->image = sprintf('%s/Loader?id=%s&dimension=%s',
			$this->getComponentName(), $this->photo->id, 'small');

		$preview->preview_width = $this->photo->getWidth('large');
		$preview->preview_height = $this->photo->getHeight('large');
		$preview->preview_image = sprintf('%s/Loader?id=%s&dimension=%s',
			$this->getComponentName(), $this->photo->id, 'large');
	}

	// }}}
	// {{{ protected function buildPendingCount()

	protected function buildPendingCount()
	{
		if ($this->getUpcomingPhotoCount() > 0) {
			$this->ui->getWidget('proceed_button')->visible = true;

			if ($this->pending) {
				$this->ui->getWidget('status_info')->content =
					sprintf(Pinhole::ngettext(
						'%d pending photo left.',
						'%d pending photos left.',
						$this->getUpcomingPhotoCount()),
						$this->getUpcomingPhotoCount());
			}
		}
	}

	// }}}
	// {{{ protected function buildSiteLinks()

	protected function buildSiteLinks()
	{
		$replicator = $this->ui->getWidget('site_link_field');
		foreach ($this->getDimensions() as $dimension) {
			if ($dimension->shortname == 'original') {
				$image = $this->photo->getTitle(true);
				$link = $this->photo->getUri('original');
			} else {
				$image = $this->photo->getImgTag($dimension->shortname);
				$image->src = $this->app->getFrontendBaseHref().$image->src;
				$link = 'photo/'.$this->photo->id;
			}

			$code = sprintf('<a href="%s%s">%s</a>',
				$this->app->getFrontendBaseHref(), $link, $image);

			$replicator->getWidget('site_link_code', $dimension->id)->value =
				$code;
		}
	}

	// }}}
	// {{{ protected function buildButton()

	protected function buildButton()
	{
		// don't do anything here, Admin wants to rename the button
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->photo));

		$tags = array();

		foreach ($this->photo->tags as $tag)
			$tags[$tag->name] = $tag->title;

		$this->ui->getWidget('tags')->setSelectedTagArray($tags);

		// sets the date to the set timezone
		$converted_date = $this->photo->photo_date;
		if ($converted_date !== null)
			$converted_date->convertTZbyID($this->photo->photo_time_zone);

		$this->ui->getWidget('photo_date')->value = $converted_date;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/admin/styles/pinhole-photo-edit-page.css',
			Pinhole::PACKAGE_ID));

		$this->layout->addHtmlHeadEntry(new SwatJavascriptHtmlHeadEntry(
			'packages/pinhole/admin/javascript/pinhole-photo-edit-page.js',
			Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
