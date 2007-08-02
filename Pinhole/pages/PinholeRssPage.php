<?php

require_once 'Swat/SwatUI.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatImageDisplay.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/exceptions/SwatWidgetNotFoundException.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/PinholeTagList.php';
require_once 'Pinhole/pages/PinholePage.php';
require_once 'Pinhole/layouts/PinholeRssLayout.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeRssPage extends PinholePage
{
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout)
	{
		$layout = new PinholeRssLayout($app, 'Pinhole/layouts/xhtml/rss.php');

		parent::__construct($app, $layout);

		$tags = SiteApplication::initVar('tags');
		$this->createTagList($tags);
	}

	// }}}
	// {{{ protected function createTagList()

	protected function createTagList($tags)
	{
		$this->tag_list = new PinholeTagList($this->app->db, $tags);
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->tag_list->setInstance($this->app->instance->getInstance());
		$this->tag_list->setPhotoRange(new SwatDBRange(50));

		$this->tag_list->setPhotoWhereClause(sprintf(
			'PinholePhoto.status = %s',
			$this->app->db->quote(PinholePhoto::STATUS_PUBLISHED, 'integer')));

		$this->tag_list->setPhotoOrderByClause(
			'PinholePhoto.publish_date desc, id desc');
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->startCapture('feed');
		$this->displayFeed();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function displayFeed()

	protected function displayFeed()
	{
		$photos = $this->tag_list->getPhotos();

		foreach ($photos as $photo) {

			echo '<item>';

			printf('<title>%s</title>',
				SwatString::minimizeEntities($photo->title));

			printf('<link>%stag/photo/%s</link>',
				$this->app->getBaseHref(),
				$photo->id);

			echo "<content:encoded><![CDATA[\n";
			$this->displayContent($photo);
			echo ']]></content:encoded>';


			printf('<guid>%stag/photo/%s</guid>',
				$this->app->getBaseHref(),
				$photo->id);

			$date = ($photo->photo_date === null) ? new SwatDate() :
				$photo->photo_date;
			printf('<dc:date>%s</dc:date>',
				$date->format('%Y-%m-%dT%H:%M:%S%O'));

			printf('<dc:creator>%s</dc:creator>',
				''); //TODO: populate this with photographer

			echo '</item>';
		}
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent($photo)
	{
		$dimension = $photo->getDimension('large');

		$div_tag = new SwatHtmlTag('div');
		$div_tag->open();

		$image = new SwatImageDisplay();
		$image->image  = $this->app->getBaseHref().$dimension->getUri();
		$image->width  = $dimension->width;
		$image->height = $dimension->height;
		$image->display();

		if ($photo->description !== null) {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->setContent($photo->description, 'text/xml');
			$div_tag->display();
		}

		$div_tag->close();
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
	}

	// }}}
}

?>
