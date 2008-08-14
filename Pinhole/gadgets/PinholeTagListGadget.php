<?php

require_once 'Site/gadgets/SiteGadget.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'SwatI18N/SwatI18NLocale.php';
require_once 'Pinhole/PinholeSubTagListView.php';

/**
 * Displays a list of tags
 *
 * Available settings are:
 *
 * - integer limit controls how many tags will be displayed
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeTagListGadget extends SiteGadget
{
	// {{{ protected properties

	protected $tag_list_view;

	// }}}
	// {{{ public function display()

	public function display()
	{
		parent::display();

		$this->html_head_entry_set->addEntrySet(
			$this->tag_list_view->getHtmlHeadEntrySet());
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$range = new SwatDBRange($this->getValue('limit'));

		$tag_list = new PinholeTagList($this->app->db,
			$this->app->getInstance());

		$sub_tag_list = $tag_list->getSubTags($range);
		$sub_tag_count = $tag_list->getSubTagCount();

		$this->tag_list_view = new PinholeSubTagListView();
		$base_path = $this->app->config->pinhole->path;
		$this->tag_list_view->base = $base_path.'tag';
		$this->tag_list_view->setTagList($tag_list);
		$this->tag_list_view->setSubTagList($sub_tag_list);
		$this->tag_list_view->display();

		if ($sub_tag_count > count($sub_tag_list)) {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'pinhole-sub-tag-more-link';
			$div_tag->open();

			$a_tag = new SwatHtmlTag('a');
			$a_tag->href = $base_path.'tags/date';
			$a_tag->setContent(sprintf(Pinhole::_('View All %s Tags'),
				$sub_tag_count));

			$a_tag->display();

			$div_tag->close();
		}
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Pinhole::_('Recently Added Tags'));
		$this->defineSetting('limit', Pinhole::_('Limit'), 'integer', 15);
		$this->defineDescription(Pinhole::_(
			'Displays a list of tags.'));
	}

	// }}}
}

?>
