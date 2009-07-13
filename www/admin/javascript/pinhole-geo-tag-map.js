function PinholeGeoTagMap(container_id, start_lat, start_lng, start_zoom)
{
	this.container_id = container_id;

	this.start_lat = (start_lat) ? start_lat : 46.236378;
	this.start_lng = (start_lng) ? start_lng : -63.129694;
	this.start_zoom = (start_zoom) ? start_zoom : 5;

	YAHOO.util.Event.addListener(window, 'load',
		this.buildMap, this, true);
}

PinholeGeoTagMap.processing_text = 'Updating…';

// {{{ PinholeGeoTagMap.prototype.buildMap

PinholeGeoTagMap.prototype.buildMap = function()
{
	var button_title = document.getElementById('set_gps').value;

	if (GBrowserIsCompatible()) {
		var map = new GMap2(document.getElementById(this.container_id), {
			googleBarOptions: {
				resultList: G_GOOGLEBAR_RESULT_LIST_SUPPRESS,
				suppressInitialResultSelection: true
			}
		});

		map.setCenter(new GLatLng(this.start_lat, this.start_lng),
			this.start_zoom);

		var ui = map.getDefaultUI();
		ui.controls.scalecontrol = false;
		map.setUI(ui);
		map.enableGoogleBar();

		var marker;

		GEvent.addListener(map, "click", function(overlay, latlng) {
			if (marker) {
				marker.setLatLng(latlng);
			} else {
				var options = {
					draggable: true
				};

				marker = new GMarker(latlng, options);
				map.addOverlay(marker);
			}
		});

		YAHOO.util.Event.addListener(document.getElementById('set_gps'),
			'click', function (e)
		{
			YAHOO.util.Event.preventDefault(e);

			if (!marker) {
				alert('You must specify a point on the map.');
				return;
			}

			var latlng = marker.getLatLng();
			var lat = latlng.lat();
			var lng = latlng.lng();
			var zoom_level = map.getZoom();

			var ids = [];

			var iframe_element = document.getElementById('search_iframe');
			if (iframe_element.contentDocument) {
				var iframe = iframe_element.contentDocument;
			} else {
				// IE does things differently
				var iframe = iframe_element.contentWindow;
			}

			var items = iframe.getElementsByName('items[]');

			for (var i = 0; i < items.length; i++) {
				if (items[i].checked)
					ids.push(items[i].value);
			}

			if (ids.length == 0) {
				alert('You must check at least one photo.');
				return;
			}

			function callBack(response) {
				document.getElementById('set_gps').value = button_title;

				if (response.length == 0)
					return;

				var tiles = YAHOO.util.Dom.getElementsByClassName(
					'swat-tile', 'div', iframe);

				for (var i = 0; i < tiles.length; i++) {
					var checkboxes = YAHOO.util.Dom.getElementsBy(
						function (element) { return element.name == 'items[]'},
						'input', tiles[i]);

					if (checkboxes.length != 0) {
						for (var j = 0; j < response.length; j++) {
							if (checkboxes[0].value == response[j]) {
								YAHOO.util.Dom.addClass(tiles[i], 'geo-tagged');
								break;
							}
						}
					}
				}

				if (document.getElementById('auto_next').checked) {
					var found = false;
					var last_item = null;
					var items = iframe.getElementsByName('items[]');

					for (var i = 0; i < items.length; i++) {
						if (items[i].checked) {
							items[i].checked = false;
							found = true;
						} else if (found) {
							last_item = items[i];
							found = false;
						}
					}

					if (last_item) {
						last_item.checked = true;
						last_item.focus();
					}
				}
			}

			document.getElementById('set_gps').value =
				PinholeGeoTagMap.processing_text;

			// do xml-rpc update of photos
			var client = new XML_RPC_Client('GeoTag/PhotoGpsServer');
			client.callProcedure('setPhotoGpsData', callBack,
				[ids, lat, lng, zoom_level],
				['array', 'double', 'double', 'int']);
		});
	}
}

// }}}
