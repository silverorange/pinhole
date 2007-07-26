<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatUI.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatDetailsViewField.php';
require_once 'Swat/SwatTextCellRenderer.php';
require_once 'Swat/SwatLinkCellRenderer.php';
require_once 'Swat/SwatDate.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/PinholeDateTagCellRenderer.php';
require_once 'Pinhole/pages/PinholeBrowserPage.php';
require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'Pinhole/dataobjects/PinholeComment.php';
require_once 'Pinhole/dataobjects/PinholeCommentWrapper.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeBrowserDetailsPage extends PinholeBrowserPage
{
	// {{{ protected properties

	protected $photo;
	protected $details_ui;
	protected $details_ui_xml = 'Pinhole/pages/browser-details-view.xml';

	// }}}

	// init phase
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout,
		$photo_id = null, $tags = null)
	{
		parent::__construct($app, $layout, $tags);

		// TODO: use classmap
		$this->photo = new PinholePhoto();
		$this->photo->setDatabase($this->app->db);
		$this->photo->load(intval($photo_id));

		$this->comment = new PinholeComment();
		$this->comment->setDatabase($this->app->db);
	}

	// }}}
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->details_ui = new SwatUI();
		$this->details_ui->mapClassPrefixToPath('Pinhole', 'Pinhole');
		$this->details_ui->loadFromXML($this->details_ui_xml);
		$this->details_ui->init();
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$form = $this->details_ui->getWidget('reply');
		$form->process();
		$form->action = sprintf('photo/%s',$this->photo->id);

		if ($form->isSubmitted() && ($this->photo->comments_status == 0)) {
			$recaptcha = $this->details_ui->getWidget('recaptcha');
			
			if (!$recaptcha->hasMessage()){
				$fullname   = $this->details_ui->getWidget('fullname');
				$email      = $this->details_ui->getWidget('email');
				$bodytext   = $this->details_ui->getWidget('bodytext');
				$webaddress = $this->details_ui->getWidget('webaddress');
				$rating     = $this->details_ui->getWidget('rating_flydown');

				$date = new SwatDate();
				$this->comment->fullname    = $fullname->value;
				$this->comment->email       = $email->value;
				$this->comment->bodytext    = $bodytext->value;
				$this->comment->webaddress  = $webaddress->value;
				$this->comment->rating      = $rating->value;
				$this->comment->photo       = $this->photo->id;
				$this->comment->create_date = $date;
				$this->comment->save();

				// resets the fields to blank
				$fullname->value   = null;
				$email->value      = null;
				$bodytext->value   = null;
				$webaddress->value = null;
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

		$view = $this->details_ui->getWidget('photo_details_view');
		$view->data = $this->getPhotoDetailsStore();
		$this->buildMetaData();

		$description = $this->details_ui->getWidget('description');
		$description->content = $this->photo->description;
		
		$this->buildComments($this->getCommentsStore());

		/* Set YUI Grid CSS class for one full-width column on details page */
		$this->layout->data->yui_grid_class = 'yui-t7';

		$this->layout->data->title =
			SwatString::minimizeEntities($this->photo->title);

		$this->layout->startCapture('content');
		$this->displayPhoto();
		$this->details_ui->display();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function displayHeaderContent()

	protected function displayHeaderContent()
	{
		$this->displayIntersectingTags();
		$this->displayNavigationLinks();
	}

	// }}}
	// {{{ protected function getPhotoDetailsStore()

	protected function getPhotoDetailsStore()
	{
		$store = new SwatDetailsStore($this->photo);

		return $store;
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
	// {{{ protected function displayNavigationLinks()

	protected function displayNavigationLinks()
	{
		$next_prev = $this->tag_intersection->getNextPrevPhoto($this->photo);
		$next = $next_prev['next'];
		$prev = $next_prev['prev'];

		$tag_path = $this->tag_intersection->getIntersectingTagPath();
		$tag_path = ($tag_path === null) ? '' : '/'.$tag_path;

		$a_tag = new SwatHtmlTag('a');
		$span_tag = new SwatHtmlTag('span');
		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = 'photo_next_prev';

		$div_tag->open();

		if ($prev === null) {
			$span_tag->setContent('Prev');
			$span_tag->display();
		} else {
			$a_tag = new SwatHtmlTag('a');
			$a_tag->href = sprintf('photo/%s%s',
				$prev->id,
				$tag_path);
			$a_tag->title = $prev->title;
			$a_tag->class = 'prev';
			$a_tag->setContent('« Prev');
			// this is a non-breaking space
			$a_tag->display();
		}

		$a_tag->title = null;
		$a_tag->setContent('View All');
		$a_tag->href = 'tag'.$tag_path;
		$a_tag->class = 'view-all';
		echo ' ';
		$a_tag->display();
		echo ' ';

		if ($next === null) {
			$span_tag->setContent('Next');
			$span_tag->display();
		} else {
			$a_tag->href = sprintf('photo/%s%s',
				$next->id,
				$tag_path);
			$a_tag->title = $next->title;
			$a_tag->class = 'next';
			$a_tag->setContent('Next »');
			// this is a non-breaking space
			$a_tag->display();
		}

		$div_tag->close();
	}

	// }}}
	// {{{ protected function getTagListTags()

	protected function getTagListTags()
	{
		$tags = new PinholeTagWrapper();
		$intersection_tags = parent::getTagListTags();
		$photo_tags = $this->photo->tags;
		foreach ($intersection_tags as $tag)
			if ($photo_tags->getByIndex($tag->id) !== null)
				$tags->add($tag);

		return $tags;
	}

	// }}}
	// {{{ protected function buildMetaData()

	protected function buildMetaData()
	{
		$view = $this->details_ui->getWidget('photo_details_view');

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
		$comments = $this->details_ui->getWidget('comments');
		$comments_status = $this->photo->comments_status;
		
		if ($comments_status == PinholePhoto::COMMENTS_STATUS_NORMAL
			|| $comments_status == PinholePhoto::COMMENTS_STATUS_LOCKED) {

			foreach ($store as $comment) {

				$content_block = new SwatContentBlock();
				$content_block->content_type = 'text/xml';

				$date = new SwatDate($comment->create_date);

				$content = sprintf('%s - %s<br />',
					$comment->fullname,
					$date->format(SwatDate::DF_DATE_TIME_SHORT));

				if ($comment->rating) {
					$content .= sprintf('Rating:%s <br />', 
						(str_repeat('٭', $comment->rating)));
				}

				if ($comment->email)
					$content.= sprintf('<a href="mailto:%s">%s</a><br />',
						$comment->email, $comment->email);

				if ($comment->webaddress)
					$content.= sprintf('<a href="%s">%s</a><br>',
						$comment->webaddress, $comment->webaddress);

				$content.= $comment->bodytext.'<br><br>';
				$content_block->content = $content;

				$comments->add($content_block);
			}
		
		// build rating flydown
		$ratings = array(1 => '٭', 2 => '٭٭', 3 => '٭٭٭', 
			4 => '٭٭٭٭', 5 => '٭٭٭٭٭');

		$flydown = $this->details_ui->getWidget('rating_flydown');
		$flydown->addOptionsByArray($ratings);
		}
	}

	// }}}
	// {{{ protected function getCommentsStore()

	protected function getCommentsStore()
	{
		$sql = sprintf('select fullname, email, webaddress,
					bodytext, create_date, rating
				from PinholeComment
				where photo = %s
				order by create_date',
			$this->photo->id);

		$sections = SwatDB::query($this->app->db, $sql, 'PinholeCommentWrapper');

		return $sections;
	}

	// }}}


	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->details_ui->getRoot()->getHtmlHeadEntrySet());

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/styles/pinhole-browser-details-page.css',
			Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
