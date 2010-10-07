<?php

require_once 'Site/pages/SiteXMLRPCServer.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Pinhole/gadgets/PinholeCalendarGadget.php';

/**
 * Handles XML-RPC requests from the pinhole calendar gadget
 *
 * @package   Pinhole
 * @copyright 2006-2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeCalendarGadgetServer extends SiteXMLRPCServer
{
	// {{{ protected properties

	protected $date;

	// }}}
	// {{{ public function getCalendar()

	/**
	 * Returns an XHTML calendar with photo counts
	 *
	 * @param integer $year The year to display
	 * @param integer $month The month to display
	 *
	 * @return string an XHTML calendar.
	 */
	public function getCalendar($year, $month)
	{
		$date = new SwatDate();
		$date->setDate($year, $month, 1);
		$date->setTime(0, 0, 0);

		ob_start();
		PinholeCalendarGadget::displayCalendarMonth($this->app, $date);
		$response['calendar_month'] = ob_get_clean();

		ob_start();
		PinholeCalendarGadget::displayCalendarBody($this->app, $date);
		$response['calendar_body'] = ob_get_clean();

		return $response;
	}

	// }}}
}

?>
