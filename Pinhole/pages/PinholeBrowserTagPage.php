<?php

require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Pinhole/pages/PinholeBrowserPage.php';
require_once 'Pinhole/tags/PinholePageTag.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeBrowserTagPage extends PinholeBrowserPage
{
	// {{{ protected properties

	protected $display_type;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout,
		$display_type = 'alphabetical', $tags = '')
	{
		parent::__construct($app, $layout, $tags);

		$this->ui_xml = 'Pinhole/pages/browser-tag.xml';
		$this->display_type = $display_type;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		if (isset($this->layout->navbar))
			$this->layout->navbar->createEntry(Pinhole::_('Tags'));

		// Set YUI Grid CSS class for one full-width column on details page.
		$this->layout->data->yui_grid_class = 'yui-t7';

		$this->layout->data->title = SwatString::minimizeEntities(
			Pinhole::_('Tags'));
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->ui->getWidget('tag_menu')->setTagList($this->tag_list);

		$this->buildTags();
	}

	// }}}
	// {{{ protected function buildTags()

	protected function buildTags()
	{
		ob_start();

		if ($this->display_type == 'alphabetical') {
			$tag_list = $this->tag_list->getSubTags(null, 'PinholeTag.title');
			if (count($tag_list) < 30)
				$this->displaySimpleList($tag_list);
			else
				$this->displayAlphabeticalList($tag_list);
		} elseif ($this->display_type == 'cloud') {
			$tag_list = $this->tag_list->getSubTagsByPopularity(
				null, 'PinholeTag.title');
			$this->displayCloud($tag_list);
		} elseif ($this->display_type == 'popular') {
			$tag_list = $this->tag_list->getSubTagsByPopularity();
			$this->displaySimpleList($tag_list);
		}

		$this->ui->getWidget('tag_list')->content = ob_get_clean();
	}

	// }}}
	// {{{ protected function displaySimpleList()

	protected function displaySimpleList(PinholeTagList $tag_list)
	{
		$ul_tag = new SwatHtmlTag('ul');
		$li_tag = new SwatHtmlTag('li');
		$ul_tag->open();

		foreach ($tag_list as $tag) {
			$li_tag->open();
			$this->displayTag($tag);
			$li_tag->close();
		}

		$ul_tag->close();
	}

	// }}}
	// {{{ protected function displayAlphabeticalList()

	protected function displayAlphabeticalList(PinholeTagList $tag_list)
	{
		$grouped_tags = array();
		foreach ($tag_list as $tag) {
			$entity = strtoupper($this->convertAccents(
				substr($tag->title, 0, 1)));

			if (is_numeric($entity))
				$entity = '0';

			ob_start();
			$this->displayTag($tag);
			$grouped_tags[$entity][] = ob_get_clean();
		}

		$count = 0;

		$ul_tag = new SwatHtmlTag('ul');
		$li_tag = new SwatHtmlTag('li');
		$ul_tag->open();

		foreach ($grouped_tags as $entity => $group) {
			$li_tag->open();
			if (!is_numeric($entity)) {
				$h2_tag = new SwatHtmlTag('h2');
				$h2_tag->class = 'pinhole-tag-entity';
				$h2_tag->setContent($entity);
				$h2_tag->display();
			}

			echo implode(', ', $group);

			$li_tag->close();
		}

		$ul_tag->close();
	}

	// }}}
	// {{{ protected function displayCloud()

	protected function displayCloud(PinholeTagList $tag_list)
	{
		$max = null;
		$min = null;

		foreach ($tag_list as $tag) {
			$max = max($max, $tag->photo_count);
			$min = min($min, $tag->photo_count);
		}

		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'pinhole-tag-cloud';
		$div_tag->open();

		$span_tag = new SwatHtmlTag('span');

		foreach ($tag_list as $tag) {
			$scale = ceil(($tag->photo_count - $min) / ($max - $min) * 200) + 100;

			$span_tag->style = sprintf('font-size: %s%%;',
				$scale);

			$span_tag->open();
			$this->displayTag($tag);
			$span_tag->close();
			echo ' ';
		}

		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayTag()

	protected function displayTag(PinholeTag $tag)
	{
		$add_list = clone $this->tag_list;
		$add_list->add($tag);

		$add_anchor_tag = new SwatHtmlTag('a');
		$add_anchor_tag->rel = 'tag';
		$add_anchor_tag->href = $this->app->config->pinhole->path.'tag?'.
			$add_list->__toString();
		$add_anchor_tag->setContent($tag->getTitle());

		if ($tag->photo_count !== null) {
			$add_anchor_tag->title = sprintf(Pinhole::_('%s Photos'),
				SwatString::minimizeEntities($tag->photo_count));
		}

		$add_anchor_tag->display();
	}

	// }}}
	// {{{ private function convertAccents()

	private function convertAccents($string)
	{
		// TODO: maybe move this to SwatString
		$string = htmlentities($string, ENT_COMPAT, 'UTF-8');
		$string = preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde);/',
			'$1',$string);
		return html_entity_decode($string);
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/pinhole/styles/pinhole-browser-tag-page.css',
			Pinhole::PACKAGE_ID));
	}

	// }}}
}

?>
