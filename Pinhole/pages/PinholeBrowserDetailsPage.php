<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatDetailsViewField.php';
require_once 'Swat/SwatTextCellRenderer.php';
require_once 'Swat/SwatLinkCellRenderer.php';
require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatString.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/pages/PinholeBrowserPage.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'Pinhole/dataobjects/PinholeComment.php';
require_once 'Pinhole/dataobjects/PinholeCommentWrapper.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeBrowserDetailsPage extends PinholeBrowserPage
{
	// {{{ protected properties

	/**
	 * @var PinholePhoto
	 */
	protected $photo;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout,
		$photo_id = null, $tags = '')
	{
		parent::__construct($app, $layout, $tags);
		$this->ui_xml = 'Pinhole/pages/browser-details.xml';

		$this->createPhoto($photo_id);

		$this->comment = new PinholeComment();
		$this->comment->setDatabase($this->app->db);

	}

	// }}}
	// {{{ protected function createPhoto()

	protected function createPhoto($photo_id)
	{
		$photo_id = intval($photo_id);
		$photo_class = SwatDBClassMap::get('PinholePhoto');
		$this->photo = new $photo_class();
		$this->photo->setDatabase($this->app->db);
		$this->photo->load($photo_id);
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$form = $this->ui->getWidget('reply');
		$form->action = sprintf('photo/%s',$this->photo->id);

		if ($form->isSubmitted() && ($this->photo->comments_status == 0)) {
			$recaptcha = $this->ui->getWidget('recaptcha');
			
			if (!$recaptcha->hasMessage()){
				$name       = $this->ui->getWidget('name');
				$email      = $this->ui->getWidget('email');
				$bodytext   = $this->ui->getWidget('bodytext');
				$url        = $this->ui->getWidget('url');
				$rating     = $this->ui->getWidget('rating_flydown');

				$date = new SwatDate();

				$this->comment->name        = 
					SwatString::minimizeEntities($name->value);

				$this->comment->bodytext    = 
					SwatString::minimizeEntities($bodytext->value);

				$this->comment->url         = 
					SwatString::minimizeEntities(ltrim($url->value, 'http://'));

				$this->comment->rating      = $rating->value;
				$this->comment->email       = $email->value;
				$this->comment->photo       = $this->photo->id;
				$this->comment->createdate  = $date;
				$this->comment->remote_ip   = $_SERVER['REMOTE_ADDR'];
				$this->comment->save();

				// resets the fields to blank
				$name->value       = null;
				$email->value      = null;
				$bodytext->value   = null;
				$url->value        = null;
				$rating->value     = null;
			}
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		// Set YUI Grid CSS class for one full-width column on details page.
		$this->layout->data->yui_grid_class = 'yui-t7';

		// Set photo title.
		$this->layout->data->title =
			SwatString::minimizeEntities($this->photo->title);
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$view = $this->ui->getWidget('photo_details_view');
		$view->data = $this->getPhotoDetailsStore();

		$this->buildMetaData();
		$this->buildPhotoScroller();

		$description = $this->ui->getWidget('description');
		$description->content = $this->photo->description;
		
		$this->buildComments($this->getCommentsStore());
	}

	// }}}
	// {{{ protected function buildMetaData()

	protected function buildMetaData()
	{
		$view = $this->ui->getWidget('photo_details_view');

		foreach ($this->photo->meta_data as $meta_data) {
			$field = new SwatDetailsViewField();
			$field->title = $meta_data->title;

			if ($meta_data->machine_tag) {
				$renderer = new SwatLinkCellRenderer();
				$renderer->link = sprintf('tag/meta.%s=%s',
					$meta_data->shortname,
					$meta_data->value);
			} else {
				$renderer = new SwatTextCellRenderer();
			}

			$renderer->text = $meta_data->value;

			$view->appendField($field);
			$field->addRenderer($renderer);
		}

	}

	// }}}
	// {{{ protected function buildComments()
	
	protected function buildComments($store)
	{
		// build previous comments
		$comments = $this->ui->getWidget('comments');
		$comments_status = $this->photo->comments_status;
		$hide_comments = 0;

		foreach ($store as $comment) {
			if (!$comment->show)
				$hide_comments += 1;
		}

		$this->ui->getWidget('comments_fieldset')->visible =
			(count($store) > 0 &&
			$comments_status != PinholePhoto::COMMENTS_STATUS_DISABLED);

		if ($hide_comments === count($store))
			$this->ui->getWidget('comments_fieldset')->visible = false;

		if ($comments_status == PinholePhoto::COMMENTS_STATUS_NORMAL
			|| $comments_status == PinholePhoto::COMMENTS_STATUS_LOCKED) {

			foreach ($store as $comment) {
				if (!$comment->show)
					return;

				$content_block = new SwatContentBlock();
				$content_block->content_type = 'text/xml';

				$date = new SwatDate($comment->createdate);

				$content = sprintf('%s - %s<br />',
					$comment->name,
					$date->format(SwatDate::DF_DATE_TIME_SHORT));

				if ($comment->rating) {
					$content .= sprintf('Rating:%s <br />', 
						(str_repeat('٭', $comment->rating)));
				}

				if ($comment->url)
					$content.= sprintf('<a href="http://%s">%s</a><br>',
						$comment->url, $comment->url);

				$content.= $comment->bodytext.'<br><br>';
				$content_block->content = $content;

				$comments->add($content_block);
			}
		
		// build rating flydown
		$ratings = array(1 => '٭', 2 => '٭٭', 3 => '٭٭٭', 
			4 => '٭٭٭٭', 5 => '٭٭٭٭٭');

		$flydown = $this->ui->getWidget('rating_flydown');
		$flydown->addOptionsByArray($ratings);
		}

		// checks to see if the reply disclosure has any error messages
		$reply = $this->ui->getWidget('reply_disclosure');
		if ($reply->hasMessage())
			$reply->open = true;
	}

	// }}}
	// {{{ protected function buildPhotoScroller()

	protected function buildPhotoScroller()
	{
		$photo_scroller = $this->ui->getWidget('photo_scroller');
		$photo_scroller->setPhoto($this->photo);
		$photo_scroller->setTagList($this->tag_list);
	}

	// }}}
	// {{{ protected function getCommentsStore()

	protected function getCommentsStore()
	{
		$sql = sprintf('select PinholeComment.*
				from PinholeComment
				where photo = %s
				order by createdate desc',
			$this->photo->id);

		$sections = SwatDB::query($this->app->db, $sql, 'PinholeCommentWrapper');

		return $sections;
	}

	// }}}
	// {{{ protected function getPhotoDetailsStore()

	protected function getPhotoDetailsStore()
	{
		$store = new SwatDetailsStore($this->photo);
		return $store;
	}

	// }}}
	// {{{ protected function getSubTagList()

	protected function getSubTagList()
	{
		return parent::getSubTagList()->intersect($this->photo->tags);
	}

	// }}}
	// {{{ protected function displayPhoto()

	protected function displayPhoto()
	{
		$img_tag = new SwatHtmlTag('img');
		$img_tag->src = $this->photo->getDimension('large')->getUri();
		$img_tag->width = $this->photo->getDimension('large')->width;
		$img_tag->height = $this->photo->getDimension('large')->height;
		$img_tag->alt = $this->photo->title;
		$img_tag->class = 'pinhole-photo';
		$img_tag->display();
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$this->displayPhoto();
		parent::displayContent();
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/styles/pinhole-browser-details-page.css',
			Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
