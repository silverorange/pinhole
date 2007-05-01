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

		/* TODO: This is bad since it puts the link outside the tile div.
		 *       Perhaps we should add a swat tile option to output a link...
		 *       Or require overide more here...
		 *       Or do it with cell-renderers somehow...
		 */
		$anchor = new SwatHtmlTag('a');
		$anchor->href = $data->link;
		$anchor->open();
		parent::display($data);
		$anchor->close();
	}

	// }}}
}

?>
