<?php

require_once 'Site/SiteXMLRPCServerFactory.php';

/**
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeXMLRPCServerFactory extends SiteXMLRPCServerFactory
{
	// {{{ public function __construct()

	public function __construct()
	{
		parent::__construct();

		// set location to load Pinhole page classes from
		$this->class_map['Pinhole'] = 'Pinhole/pages';
	}

	// }}}
	// {{{ protected function getPageMap()

	protected function getPageMap()
	{
		return array(
			'calendar-gadget' => 'PinholeCalendarGadgetServer',
		);
	}

	// }}}
}

?>
