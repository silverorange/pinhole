<?php

require_once 'Swat/SwatTile.php';

/**
 * Tile for displaying photos
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoTile extends SwatTile
{
	// {{{ public function display()

	/**
	 * Displays this tile
	 */

	public function display($data)
	{
		if (!$this->visible)
			return;

		$anchor = new SwatHtmlTag('a');
		$anchor->href = $data->link;
		$anchor->open();
		parent::display($data);
		$anchor->close();
	}

	// }}}
}

?>
