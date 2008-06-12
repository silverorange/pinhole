<?php

require_once 'Pinhole/Pinhole.php';
require_once 'Swat/SwatNavBar.php';
require_once 'Site/layouts/SiteLayout.php';
require_once 'Pinhole/dataobjects/PinholeLayouts.php';

/**
 * Layout for pages in the Pinhole photo gallery package
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeLayout extends SiteLayout
{
	// {{{ private properties

	private $edit_layout;

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();
		$this->data->header_content = '';
		$this->data->sidebar_content = '';
		$this->data->search_content = '';
		$this->data->dates = '';
		$this->data->content = '';
		$this->data->atom_link = 'atom';

		$this->initLayouts();
		/*
		 * Default YUI Grid CSS to:
		 *  - Two columns, narrow on left 180px (yui-t2)
		 *  - 100% fluid page width (doc3)
		 * For more options, see: http://developer.yahoo.com/yui/grids/
		 */
		$this->initYUIGrid();
	}

	// }}}
	// {{{ public function initLayout()

	public function initLayouts()
	{
		$this->edit_layout = new PinholeLayouts();
		$this->edit_layout->setDatabase($this->app->db);
		$this->edit_layout->load(1);
	}

	// }}}
	// {{{ public function initYUIGrid()
	public function initYUIGrid()
	{
		if ($this->edit_layout->sidebarposition === 'left') {
			switch ($this->edit_layout->sidebarwidth) {
				case '160px':
					$this->data->yui_grid_class = 'yui-t1';
					break;
				case '180px':
					$this->data->yui_grid_class = 'yui-t2';
					break;
				case '300px':
					$this->data->yui_grid_class = 'yui-t3';
					break;
			}
		} elseif ($this->edit_layout->sidebarposition === 'right') {
			switch ($this->edit_layout->sidebarwidth) {
				case '180px':
					$this->data->yui_grid_class = 'yui-t4';
					break;
				case '160px'://'240px':
				// TODO make 240px work
					$this->data->yui_grid_class = 'yui-t2';
					break;
				case '300px':
					$this->data->yui_grid_class = 'yui-t6';
					break;
			}
		} else {
			// TODO Make Bottom work
			$this->data->yui_grid_class = 'yui-t2';
		}

		switch ($this->edit_layout->pagewidth) {
			case '750px':
				$this->data->yui_grid_id = 'doc1';
				break;
			case '950px':
				$this->data->yui_grid_id = 'doc2';
				break;
			case '100%':
				$this->data->yui_grid_id = 'doc3';
				break;
			default:
				$this->data->yui_grid_id = 'doc3';
				break;
		}
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		// build html title (goes in html head)
		$instance_title = $this->app->config->site->title;
		$page_title = $this->data->title;

		if ($page_title == '')
			$this->data->html_title = $instance_title;
		else
			$this->data->html_title = $page_title.' - '.$instance_title;

		// build displayed title (top of page)
		$this->data->instance_title = $instance_title;
	}

	// }}}
}

?>
