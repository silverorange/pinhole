<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'PinholePhotographer.php';

/**
 * A recordset wrapper class for PinholePhotographer objects
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholePhotographer
 */
class PinholePhotographerWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->row_wrapper_class =
			$this->class_map->resolveClass('PinholePhotographer');
	}

	// }}}
}

?>
