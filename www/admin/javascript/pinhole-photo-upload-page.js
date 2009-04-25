/**
 * @copyright 2007-2009 silverorange
 */

// {{{ PinholePhotoUploadPage

PinholePhotoUploadPage = function()
{
	// time zone flydowns
	this.match_time_zone_flydowns = true;
	var photo_regions = document.getElementById('photo_time_zone_regions');
	YAHOO.util.Event.addListener(photo_regions, 'change',
		this.handleTimeZoneChange, this, true);

	var camera_regions = document.getElementById('camera_time_zone_regions');
	YAHOO.util.Event.addListener(camera_regions, 'click',
		this.disableTimeZoneMatching, this, true);

	var camera_areas = document.getElementById('camera_time_zone_areas');
	YAHOO.util.Event.addListener(camera_areas, 'click',
		this.disableTimeZoneMatching, this, true);
}

// }}}

// time zone methods
// {{{ PinholePhotoUploadPage.prototype.disableTimeZoneMatching

PinholePhotoUploadPage.prototype.disableTimeZoneMatching = function(e)
{
	this.match_time_zone_flydowns = false;
}

// }}}
// {{{ PinholePhotoUploadPage.prototype.handleTimeZoneChange

PinholePhotoUploadPage.prototype.handleTimeZoneChange = function(e)
{
	if (!this.match_time_zone_flydowns)
		return;

	document.getElementById('camera_time_zone_areas').selectedIndex =
		document.getElementById('photo_time_zone_areas').selectedIndex;

	camera_time_zone_regions_cascade.update();

	document.getElementById('camera_time_zone_regions').selectedIndex =
		document.getElementById('photo_time_zone_regions').selectedIndex;
}

// }}}
