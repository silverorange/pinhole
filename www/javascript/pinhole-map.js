function PinholeMap(container_id, tag_list, photo_id)
{
	this.container_id = container_id;
	this.tag_list = tag_list;
	this.markers = [];
	this.photo_id = photo_id;

	YAHOO.util.Event.addListener(window, 'load',
		this.buildMap, this, true);
}

// {{{ PinholeMap.prototype.buildMap

PinholeMap.prototype.buildMap = function()
{
	if (GBrowserIsCompatible()) {
		var options = {
			googleBarOptions: {
				resultList: G_GOOGLEBAR_RESULT_LIST_SUPPRESS,
				suppressInitialResultSelection: true
			}
		}

		this.map = new GMap2(document.getElementById(
			this.container_id, options));

		var ui = this.map.getDefaultUI();
		ui.controls.scalecontrol = false;
		this.map.setUI(ui);
		this.map.enableGoogleBar();

		this.setCenter();

		var self = this;
		getMarkerDisplayFunction = function(lat_lng, photos)
		{
			return function() {
				self.setMarkerContent(lat_lng, photos);
			}
		}

		var marker_display = [];
		var open_photo_id = false;
		var open_lat_lng = false;

		for (var i = 0; i < this.markers.length; i++) {
			var marker = this.markers[i];
			var lat_lng = new GLatLng(marker.latitude, marker.longitude);

			for (var j = 0; j < marker.photos.length; j++) {
				if (j == 0) {
					var gmarker = new GMarker(lat_lng);
					gmarker.photos = marker.photos;

					var display_function = getMarkerDisplayFunction(
						lat_lng, marker.photos);

					GEvent.addListener(gmarker, "click", display_function);

					if (marker.photos[j] == this.photo_id) {
						open_photo_id = this.photo_id;
						open_lat_lng = lat_lng;
					}
				} else {
					var gmarker = new GMarker(lat_lng, { hide: true });
					gmarker.photos = [];
				}

				marker_display.push(gmarker);
			}
		}

		if (open_photo_id && open_lat_lng) {
			this.map.setCenter(open_lat_lng);
			this.map.setZoom(12);
			this.setMarkerContent(open_lat_lng, [open_photo_id]);
		}

		MarkerClusterer.single_click_callback = function(lat_lng, photos) {
			self.setMarkerContent(lat_lng, photos);
		};

		var markerClusterer = new MarkerClusterer(this.map, marker_display);
	}
}

// }}}
// {{{ PinholeMap.prototype.setCenter

PinholeMap.prototype.setCenter = function()
{
	for (var i = 0; i < this.markers.length; i++) {
		var marker = this.markers[i];

		if (i == 0) {
			var min_latitude = marker.latitude;
			var max_latitude = marker.latitude;
			var min_longitude = marker.longitude;
			var max_longitude = marker.longitude;
		} else {
			min_latitude = Math.min(min_latitude, marker.latitude);
			max_latitude = Math.max(max_latitude, marker.latitude);
			min_longitude = Math.min(min_longitude, marker.longitude);
			max_longitude = Math.max(max_longitude, marker.longitude);
		}
	}

	var bounds = new GLatLngBounds(
		new GLatLng(min_latitude, min_longitude),
		new GLatLng(max_latitude, max_longitude));

	var region = YAHOO.util.Dom.getRegion(this.container_id);

	var size = new GSize(region.right - region.left,
		region.bottom - region.top);

	var zoom_level = this.map.getBoundsZoomLevel(bounds, size);

	var center = new GLatLng((min_latitude + max_latitude) / 2,
		(min_longitude + max_longitude) / 2);

	this.map.setCenter(center, zoom_level);
}

// }}}
// {{{ PinholeMap.prototype.addMarker

PinholeMap.prototype.addMarker = function(marker)
{
	this.markers.push(marker);
}

// }}}
// {{{ PinholeMap.prototype.setMarkerContent

PinholeMap.prototype.setMarkerContent = function(lat_lng, photos)
{
	var self = this;

	function callBack(content)
	{
		self.map.openInfoWindowHtml(lat_lng, content);
	}

	this.map.openInfoWindowHtml(lat_lng, 'Loading …');

	var client = new XML_RPC_Client('xml-rpc/map-marker');
	client.callProcedure('getMarkerContent', callBack,
		[photos, this.tag_list], ['array', 'string']);
}

// }}}

// {{{ function PinholeMapMarker

function PinholeMapMarker(latitude, longitude, photos, open)
{
	this.latitude  = latitude;
	this.longitude = longitude;
	this.photos = photos;
	this.open = open;
}

// }}}
