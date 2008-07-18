/**
 * Controls the asynchronous loading of months for the calendar-gadget
 *
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */

/**
 * Creates a new CalendarGadget
 *
 * @param String id
 * @param Integer year
 * @param Integer month
 */
function PinholeCalendarGadget(id, year, month)
{
	this.id = id;
	this.year = year;
	this.month = month;

	this.next_link = document.getElementById(id + '_next');
	this.prev_link = document.getElementById(id + '_prev');
	this.calendar_body = document.getElementById(id + '_body');
	this.calendar_month = document.getElementById(id + '_month');
	this.client = new XML_RPC_Client('xml-rpc/calendar-gadget');

	this.cached_month = {};
	this.cached_body = {};

	this.cached_month[this.getCacheKey()] = this.calendar_month.innerHTML;
	this.cached_body[this.getCacheKey()] = this.calendar_body.innerHTML;

	YAHOO.util.Event.addListener(this.next_link, 'click', this.getNextMonth,
		this, true);

	YAHOO.util.Event.addListener(this.prev_link, 'click', this.getPrevMonth,
		this, true);
}

PinholeCalendarGadget.prototype.getNextMonth = function(e)
{
	if (this.month == 12) {
		this.month = 1;
		this.year++;
	} else {
		this.month++;
	}

	this.loadCalendar();
	e.preventDefault();
}

PinholeCalendarGadget.prototype.getPrevMonth = function(e)
{
	if (this.month == 1) {
		this.month = 12;
		this.year--;
	} else {
		this.month--;
	}

	this.loadCalendar();
	e.preventDefault();
}

PinholeCalendarGadget.prototype.loadCalendar = function()
{
	var self = this;

	var cache_key = this.getCacheKey();

	function callBack(response)
	{
		self.calendar_month.innerHTML = response['calendar_month'];
		self.calendar_body.innerHTML = response['calendar_body'];

		self.cached_month[cache_key] = response['calendar_month'];
		self.cached_body[cache_key] = response['calendar_body'];
	}

	if (this.cached_month[cache_key] && this.cached_body[cache_key]) {
		self.calendar_month.innerHTML = this.cached_month[cache_key];
		self.calendar_body.innerHTML = this.cached_body[cache_key];
	} else {
		this.client.callProcedure('getCalendar', callBack,
			[this.year, this.month],
			['int', 'int']);
	}
}

PinholeCalendarGadget.prototype.getCacheKey = function()
{
	return this.year + '_' + this.month;
}
