/**
 * @copyright 2007 silverorange
 */

if (typeof Pinhole == 'undefined') {
	var Pinhole = {};
}

if (typeof Pinhole.page == 'undefined') {
	Pinhole.page = {};
}

(function () {
	// {{{ Pinhole.page.PendingPhotosPage

	Pinhole.page.PendingPhotosPage = function(unprocessed_photos)
	{
		if (!unprocessed_photos)
			unprocessed_photos = [];

		document.getElementById('manual_process').style.display = 'none';
		this.toggleCheckAll(false);

		this.unprocessed_photos = unprocessed_photos;
		this.total_count = unprocessed_photos.length;
		this.processing_message = document.getElementById('processing_message');
		this.processing_errors = document.getElementById('processing_errors');

		// for display
		this.current_photo = null;
		this.spacer_div = document.createElement('div');
		this.container = document.getElementById('index_view');

		this.updateMessage();

		if (this.unprocessed_photos.length > 0) {
			this.updateSensitivity();
			this.processPhoto(this.unprocessed_photos.shift());
		}
	};

	// }}}
	// {{{ class properties

	var proto = Pinhole.page.PendingPhotosPage.prototype;

	Pinhole.page.PendingPhotosPage.fade_duration = 1;
	Pinhole.page.PendingPhotosPage.tile_width = 100;
	Pinhole.page.PendingPhotosPage.tile_height = 100;
	Pinhole.page.PendingPhotosPage.tile_margin_y = 5;
	Pinhole.page.PendingPhotosPage.tile_margin_x = 10;
	Pinhole.page.PendingPhotosPage.tile_padding = 10;

	/**
	 * Check if the browser is Safari
	 *
	 * Safari's DOM importNode() method is broken so we use the IE table object
	 * model hack for it as well.
	 *
	 * @var boolean
	 */
	Pinhole.page.PendingPhotosPage.is_webkit =
		(/AppleWebKit|Konqueror|KHTML/gi).test(navigator.userAgent);

	// }}}
	// {{{ proto.processPhoto

	proto.processPhoto = function(id)
	{
		this.current_photo = id;

		var self = this;

		function callBack(response) {
			self.updateMessage();

			if (response.status == 'processed')
				self.displayPhoto(response.tile);
			else
				self.displayError(response.error_message);

			if (self.unprocessed_photos.length > 0)
				self.processPhoto(self.unprocessed_photos.shift());
		}

		var client = new XML_RPC_Client('Photo/ProcessorServer');
		client.callProcedure('processPhoto', callBack, [id], 'int');
	}

	// }}}
	// {{{ proto.updateMessage

	proto.updateMessage = function()
	{
		// TODO: make translatable
		var photo_count = this.total_count - this.unprocessed_photos.length;

		if (photo_count == this.total_count) {
			this.processing_message.innerHTML = 'Processing complete!';
		} else {
			this.processing_message.innerHTML =
				'Processing photo ' + (photo_count + 1) + ' of ' + this.total_count;
		}
	}

	// }}}
	// {{{ proto.displayError

	proto.displayError = function(error_message)
	{
		var div = document.createElement('div');
		div.className = 'processing-error';
		div.innerHTML = error_message;
		this.processing_errors.appendChild(div);
	}

	// }}}
	// {{{ proto.displayPhoto

	proto.displayPhoto = function(tile)
	{
		this.current_tile = tile;

		this.spacer_div.style.width = '0';
		this.spacer_div.style.height =
			(Pinhole.page.PendingPhotosPage.tile_height +
			Pinhole.page.PendingPhotosPage.tile_margin_y * 2 +
			Pinhole.page.PendingPhotosPage.tile_padding * 2) + 'px';

		this.spacer_div.style.cssFloat = 'left';
		this.spacer_div.style.margin = '0';
		this.container.insertBefore(this.spacer_div,
			this.container.firstChild);

		var animation = new YAHOO.util.Anim(this.spacer_div,
			{ width: { to:
				Pinhole.page.PendingPhotosPage.tile_width +
				Pinhole.page.PendingPhotosPage.tile_margin_x * 2 +
				Pinhole.page.PendingPhotosPage.tile_padding * 2 } },
			1, YAHOO.util.Easing.easeOutStrong);

		animation.onComplete.subscribe(this.fadeInTile, this, true);
		animation.animate();
	}

	// }}}
	// {{{ proto.fadeInTile

	proto.fadeInTile = function()
	{
		var tile = this.getTile();
		tile.style.opacity = 0;
		this.desensitize(tile);

		this.container.replaceChild(tile, this.spacer_div);
		var animation = new YAHOO.util.Anim(tile, {opacity: { to:  1} },
			Pinhole.page.PendingPhotosPage.fade_duration,
			YAHOO.util.Easing.easeInStrong);

		animation.onComplete.subscribe(this.updateSensitivity, this, true);
		animation.animate();

		index_view.init();
		items.init();
		this.toggleCheckAll(true);
	}

	// }}}
	// {{{ proto.getTile

	proto.getTile = function()
	{
		var tile_string = "<?xml version='1.0' encoding='UTF-8'?>\n" +
			'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">' +
			'<head><title>tile</title></head><body>' +
			this.current_tile +
			'</body></html>';

		var parser = this.getXMLParser();
		var dom = parser.loadXML(tile_string);
		var div_xml = dom.getElementsByTagName('div')[0];

		if (document.importNode && !Pinhole.page.PendingPhotosPage.is_webkit) {
			var div_dom = document.importNode(div_xml, true);
		} else {
			/*
			 * Internet Explorer and Safari specific code
			 *
			 * IE does not implement importNode() and Safari's importNode()
			 * implementation is broken.
			 */
			// TODO
			//var dest_tr = this.table.insertRow(this.enter_row.rowIndex);
			//SwatTableViewInputRow_parseTableRow(source_tr, dest_tr);
		}

		return div_dom;
	}

	// }}}
	// {{{ proto.getXMLParser

	proto.getXMLParser = function()
	{
		var parser = null;
		var is_ie = true;

		try {
			var dom = new ActiveXObject('Msxml2.XMLDOM');
		} catch (err1) {
			try {
				var dom = new ActiveXObject('Microsoft.XMLDOM');
			} catch (err2) {
				is_ie = false;
			}
		}

		if (is_ie) {
			/*
			 * Internet Explorer's XMLDOM object has a proprietary loadXML()
			 * method. Our method returns the document.
			 */
			parser = function() {}
			parser.loadXML = function(document_string)
			{
				if (!dom.loadXML(document_string))
					alert(dom.parseError.reason);

				return dom;
			}
		}

		if (parser === null && typeof DOMParser != 'undefined') {
			/*
			 * Mozilla, Safari and Opera have a proprietary DOMParser()
			 * class.
			 */
			dom_parser = new DOMParser();

			// Cannot add loadXML method to a newly created DOMParser because it
			// crashes Safari
			parser = function() {}
			parser.loadXML = function(document_string)
			{
				return dom_parser.parseFromString(document_string, 'text/xml');
			}
		}

		return parser;
	}

	// }}}
	// {{{ proto.toggleCheckAll

	proto.toggleCheckAll = function(visible)
	{
		var check_all = YAHOO.util.Dom.getElementsByClassName(
			'swat-check-all', 'div')

		check_all[0].style.display = (visible) ? 'block' : 'none';
	}

	// }}}
	// {{{ proto.updateSensitivity

	proto.updateSensitivity = function()
	{
		var frame = document.getElementById('index_frame');

		if (this.unprocessed_photos.length == 0) {
			this.sensitize(frame);
		} else {
			this.desensitize(frame);
		}
	}

	// }}}
	// {{{ proto.desensitize

	proto.desensitize = function(element)
	{
		var inputs = element.getElementsByTagName('input');
		var links =  element.getElementsByTagName('a');

		for (var i = 0; i < inputs.length; i++)
			inputs[i].disabled = true;

		for (var i = 0; i < links.length; i++) {
			YAHOO.util.Dom.addClass(links[i], 'disabled-link');
			YAHOO.util.Event.addListener(links[i], 'click',
				this.handleLinkCallback);
		}
	}

	// }}}
	// {{{ proto.sensitize

	proto.sensitize = function(element)
	{
		var inputs = element.getElementsByTagName('input');
		var links =  element.getElementsByTagName('a');

		for (var i = 0; i < inputs.length; i++)
			inputs[i].disabled = false;

		for (var i = 0; i < links.length; i++) {
			YAHOO.util.Dom.removeClass(links[i], 'disabled-link');
			YAHOO.util.Event.removeListener(links[i], 'click',
				this.handleLinkCallback);
		}
	}

	// }}}
	// {{{ proto.handleLinkCallback

	proto.handleLinkCallback = function(e, obj)
	{
		YAHOO.util.Event.preventDefault(e);
	}

	// }}}
})();

