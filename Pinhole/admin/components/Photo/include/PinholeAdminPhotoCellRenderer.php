<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Pinhole/PinholePhotoCellRenderer.php';

/**
 * A renderer for photo checkboxes
 *
 * @package   Pinhole
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeAdminPhotoCellRenderer extends PinholePhotoCellRenderer
{
	// {{{ protected function getUri()

	protected function getUri()
	{
		return '../'.parent::getUri();
	}

	// }}}
}

?>
