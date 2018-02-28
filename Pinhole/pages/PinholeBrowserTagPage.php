<?php

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

	public function __construct(
		SiteApplication $app,
		SiteLayout $layout,
		array $arguments
	) {
		parent::__construct($app, $layout, $arguments);

		$this->ui_xml = __DIR__.'/browser-tag.xml';
		$this->display_type = $this->getArgument('display_type');
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'display_type' => array(0, 'alphabetical'),
		);
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
			$this->getPageTitle($this->display_type));
	}

	// }}}
	// {{{ protected function getPageTitle()

	protected function getPageTitle($display_type)
	{
		$title = Pinhole::_('Tag View - %s');

		switch ($display_type) {
		case 'date' :
			return sprintf($title, Pinhole::_('By Date Added'));
		case 'alphabetical' :
			return sprintf($title, Pinhole::_('Alphabetical'));
		case 'popular' :
			return sprintf($title, Pinhole::_('By Popularity'));
		case 'cloud' :
			return sprintf($title, Pinhole::_('Cloud View'));
		}
	}

	// }}}
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->ui->getWidget('tag_menu')->setTagList($this->tag_list);
		$this->ui->getWidget('tag_menu')->base =
			$this->app->config->pinhole->path;

		$this->buildTags();
	}

	// }}}
	// {{{ protected function buildTags()

	protected function buildTags()
	{
		$cache_key = sprintf('%s.%s.%s',
			$this->cache_key, 'buildTags', $this->display_type);

		$content = $this->app->getCacheValue($cache_key, 'photos');
		if ($content !== false) {
			$this->ui->getWidget('tag_list')->content = $content;
			return;
		}

		ob_start();

		if ($this->display_type == 'alphabetical') {
			$tag_list = $this->tag_list->getSubTags(null, 'PinholeTag.title');
			if (count($tag_list) < 30)
				$this->displaySimpleList($tag_list);
			else
				$this->displayAlphabeticalList($tag_list);
		} elseif ($this->display_type == 'date') {
			$tag_list = $this->tag_list->getSubTags();

			$this->displayByDateAdded($tag_list);
		} elseif ($this->display_type == 'cloud') {
			$tag_list = $this->tag_list->getSubTagsByPopularity(
				null, 'PinholeTag.title');

			$this->displayCloud($tag_list);
		} elseif ($this->display_type == 'popular') {
			$tag_list = $this->tag_list->getSubTagsByPopularity();
			$this->displayByPopularity($tag_list);
		}

		$content = ob_get_clean();

		$this->app->addCacheValue($content, $cache_key, 'photos');
		$this->ui->getWidget('tag_list')->content = $content;
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
	// {{{ protected function displayByPopularity()

	protected function displayByPopularity(PinholeTagList $tag_list)
	{
		$ul_tag = new SwatHtmlTag('ul');
		$li_tag = new SwatHtmlTag('li');
		$ul_tag->open();

		foreach ($tag_list as $tag) {
			$li_tag->open();
			$this->displayTag($tag);

			printf(' - %s %s',
				SwatString::minimizeEntities(
					SwatString::numberFormat($tag->photo_count)),
				SwatString::minimizeEntities(Pinhole::ngettext(
					'Photo', 'Photos', $tag->photo_count)));

			$li_tag->close();
		}

		$ul_tag->close();
	}

	// }}}
	// {{{ protected function displayByDateAdded()

	protected function displayByDateAdded(PinholeTagList $tag_list)
	{
		$now = new SwatDate();
		$now->convertTZById($this->app->config->date->time_zone);

		$store = new SwatTableStore();

		foreach ($tag_list as $tag) {
			$ds = new SwatDetailsStore();
			$ds->tag = $tag;

			$tag_date = $tag->getDataObject()->first_modified;
			$tag_date->convertTZById(
				$this->app->config->date->time_zone);

			$days_past = $now->diff($tag_date)->days;

			if ($days_past <= 1)
				$ds->date_part = Pinhole::_('Today');
			elseif ($days_past <= $now->getDayOfWeek() + 1)
				$ds->date_part = Pinhole::_('This Week');
			elseif ($days_past <= $now->getDay())
				$ds->date_part = Pinhole::_('This Month');
			elseif ($days_past <= $now->getDayOfYear())
				$ds->date_part = Pinhole::_('This Year');
			else
				$ds->date_part = sprintf(Pinhole::_('%s'),
					$tag_date->getYear());

			$store->add($ds);
		}

		$ul_tag = new SwatHtmlTag('ul');
		$li_tag = new SwatHtmlTag('li');
		$ul_tag->open();
		$part = null;

		foreach ($store as $ds) {
			if ($part !== $ds->date_part) {
				if ($part !== null)
					$li_tag->close();

				$li_tag->open();
				$h2_tag = new SwatHtmlTag('h2');
				$h2_tag->class = 'pinhole-tag-entity';
				$h2_tag->setContent($ds->date_part);
				$h2_tag->display();
			} elseif ($part !== null) {
				echo ', ';
			}

			$this->displayTag($ds->tag);
			$part = $ds->date_part;
		}

		$li_tag->close();
		$ul_tag->close();
	}

	// }}}
	// {{{ protected function displayAlphabeticalList()

	protected function displayAlphabeticalList(PinholeTagList $tag_list)
	{
		$grouped_tags = array();
		foreach ($tag_list as $tag) {
			$entity = mb_strtoupper(
				$this->convertAccents(
					mb_substr($tag->title, 0, 1)
				)
			);

			if (is_numeric($entity))
				$entity = Pinhole::_('0 - 9');

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

			$h2_tag = new SwatHtmlTag('h2');
			$h2_tag->class = 'pinhole-tag-entity';
			$h2_tag->setContent($entity);
			$h2_tag->display();

			echo implode(', ', $group);

			$li_tag->close();
		}

		$ul_tag->close();
	}

	// }}}
	// {{{ protected function displayCloud()

	protected function displayCloud(PinholeTagList $tag_list)
	{
		$sorted_tag_list = array();

		foreach ($tag_list as $tag)
			$sorted_tag_list[] = $tag;

		usort($sorted_tag_list, array(&$this, 'sortTagsByTitle'));

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

		foreach ($sorted_tag_list as $tag) {
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
			$add_anchor_tag->title = sprintf('%s %s',
				SwatString::minimizeEntities(
					SwatString::numberFormat($tag->photo_count)),
				SwatString::minimizeEntities(Pinhole::ngettext(
					'Photo', 'Photos', $tag->photo_count)));
		}

		$add_anchor_tag->display();
	}

	// }}}
	// {{{ protected static function sortTagsByTitle()

	protected static function sortTagsByTitle(
		PinholeTag $tag_a,
		PinholeTag $tag_b
	) {
		$al = mb_strtolower($tag_a->title);
		$bl = mb_strtolower($tag_b->title);

		if ($al === $bl)
			return 0;

		return ($al > $bl) ? 1 : -1;
	}

	// }}}
	// {{{ private function convertAccents()

	private function convertAccents($string)
	{
		// TODO: maybe move this to SwatString
		$string = htmlentities($string, ENT_COMPAT, 'UTF-8');
		$string = preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde);/',
			'$1', $string);
		return html_entity_decode($string);
	}

	// }}}
}

?>
