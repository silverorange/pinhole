/**
 * @copyright 2007-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */

if (typeof Pinhole == 'undefined') {
	var Pinhole = {};
}

if (typeof Pinhole.page == 'undefined') {
	Pinhole.page = {};
}

(function () {

	var Dom    = YAHOO.util.Dom;
	var Event  = YAHOO.util.Event;
	var Anim   = YAHOO.util.Anim;
	var Easing = YAHOO.util.Easing;

	// {{{ Pinhole.page.PendingPhotosPage

	Pinhole.page.PendingPhotosPage = function(unprocessed_photos)
	{
		if (!unprocessed_photos)
			unprocessed_photos = [];

		document.getElementById('processing_form').style.display = 'none';
		this.toggleCheckAll(false);

		this.unprocessed_photos = unprocessed_photos;
		this.total_count = unprocessed_photos.length;

		// for display
		this.current_photo = null;
		this.spacer_div = this.getSpacer();
		this.container = document.getElementById('index_view');
		this.processing_message = document.getElementById('processing_message');
		this.processing_errors = document.getElementById('processing_errors');
		this.processing_tags = document.getElementById('processing_tags');

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
	Pinhole.page.PendingPhotosPage.tag_component = 'Tag';
	Pinhole.page.PendingPhotosPage.processor_server =
		'Photo/ProcessorServer';

	Pinhole.page.PendingPhotosPage.processing_complete_text =
		'Processing complete!';

	Pinhole.page.PendingPhotosPage.processing_text =
		'Processing photo %s of %s';

	Pinhole.page.PendingPhotosPage.edit_tag_text = 'edit';
	Pinhole.page.PendingPhotosPage.merge_tag_text = 'merge';
	Pinhole.page.PendingPhotosPage.delete_tag_text = 'delete';

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

			if (response.status == 'processed') {
				self.displayPhoto(response.tile);
				self.displayNewTags(response.new_tags);
			} else {
				self.displayError(response.error_message);
				self.updateSensitivity();
			}

			if (self.unprocessed_photos.length > 0)
				self.processPhoto(self.unprocessed_photos.shift());
		}

		this.displaySpacer();

		var client = new XML_RPC_Client('Photo/ProcessorServer');
		client.callProcedure('processPhoto', callBack, [id], 'int');
	}

	// }}}
	// {{{ proto.updateMessage

	proto.updateMessage = function()
	{
		var photo_count = this.total_count - this.unprocessed_photos.length;

		if (photo_count == this.total_count) {
			var message = Pinhole.page.PendingPhotosPage.processing_complete_text;
		} else {
			var message = Pinhole.page.PendingPhotosPage.processing_text;
			message = message.replace(/%s/, photo_count + 1);
			message = message.replace(/%s/, this.total_count);
		}

		this.processing_message.innerHTML = message;
	}

	// }}}
	// {{{ proto.displayNewTags

	proto.displayNewTags = function(tags)
	{
		for (var i = 0; i < tags.length; i++) {
			this.displayNewTag(tags[i]);
		}
	}

	// }}}
	// {{{ proto.displayNewTag

	proto.displayNewTag = function(tag)
	{
		var div_tag = document.createElement('div');
		div_tag.appendChild(document.createTextNode(tag.title + ' ('));

		var a_tag = document.createElement('a');
		a_tag.href = this.tag_component + '/Details?id=' + tag.id;
		a_tag.innerHTML = Pinhole.page.PendingPhotosPage.edit_tag_text;
		div_tag.appendChild(a_tag);

		div_tag.appendChild(document.createTextNode(', '));

		var a_tag = document.createElement('a');
		a_tag.href = this.tag_component + '/Merge?id=' + tag.id;
		a_tag.innerHTML = Pinhole.page.PendingPhotosPage.merge_tag_text;
		div_tag.appendChild(a_tag);

		div_tag.appendChild(document.createTextNode(', '));

		var a_tag = document.createElement('a');
		a_tag.href = this.tag_component + '/Delete?id=' + tag.id;
		a_tag.innerHTML = Pinhole.page.PendingPhotosPage.delete_tag_text;
		div_tag.appendChild(a_tag);

		div_tag.appendChild(document.createTextNode(')'));

		this.processing_tags.appendChild(div_tag);
		Dom.removeClass(this.processing_tags, 'swat-hidden');
	}

	// }}}
	// {{{ proto.displayError

	proto.displayError = function(error_message)
	{
		var div = document.createElement('div');
		div.innerHTML = error_message;
		this.processing_errors.appendChild(div);

		Dom.removeClass(this.processing_errors, 'swat-hidden');
	}

	// }}}
	// {{{ proto.displaySpacer

	proto.displaySpacer = function()
	{
		this.spacer_div.style.width = '0';
		this.container.insertBefore(this.spacer_div,
			this.container.firstChild);

		var animation = new Anim(this.spacer_div,
			{ width: { to: 126 } },
			1, Easing.easeOutStrong);

		animation.animate();
	}

	// }}}
	// {{{ proto.getSpacer

	proto.getSpacer = function()
	{
		var spacer_div = document.createElement('div');
		spacer_div.className = 'loading swat-tile';
		spacer_div.style.height = '180px';
		spacer_div.style.border = '0';
		return spacer_div;
	}

	// }}}
	// {{{ proto.displayPhoto

	proto.displayPhoto = function(tile_xml)
	{
		var tile = this.getTile(tile_xml);
		this.desensitize(tile);

		this.container.replaceChild(tile, this.spacer_div);

		/*
		var animation = new Anim(tile, {opacity: { to:  1} },
			Pinhole.page.PendingPhotosPage.fade_duration,
			Easing.easeInStrong);

		animation.onComplete.subscribe(this.updateSensitivity, this, true);
		animation.animate();
		*/

		index_view.init();
		items.init();
		this.toggleCheckAll(true);
		this.updateSensitivity();
	}

	// }}}
	// {{{ proto.toggleCheckAll

	proto.toggleCheckAll = function(visible)
	{
		var check_all = Dom.getElementsByClassName('swat-check-all', 'div');
		check_all[0].style.display = (visible) ? 'block' : 'none';
	}

	// }}}

	// sensitivity
	// {{{ proto.updateSensitivity

	proto.updateSensitivity = function()
	{
		var frame = document.getElementById('index_frame');

		if (this.unprocessed_photos.length == 0) {
			this.sensitize(frame);
			this.spacer_div.style.display = 'none';
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
			Dom.addClass(links[i], 'disabled-link');
			Event.on(links[i], 'click', this.handleLinkCallback);
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
			Dom.removeClass(links[i], 'disabled-link');
			Event.removeListener(links[i], 'click', this.handleLinkCallback);
		}
	}

	// }}}
	// {{{ proto.handleLinkCallback

	proto.handleLinkCallback = function(e, obj)
	{
		Event.preventDefault(e);
	}

	// }}}

	// tile building
	// {{{ proto.getTile

	proto.getTile = function(tile_xml)
	{
		var tile_string = "<?xml version='1.0' encoding='UTF-8'?>\n" +
			'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">' +
			'<head><title>tile</title></head><body>' +
			tile_xml +
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
			var div_dom = this.convertXmlToDom(div_xml);
		}

		return div_dom;
	}

	// }}}
	// {{{ proto.convertXmlToDom

	proto.convertXmlToDom = function(xml_node)
	{
		var dom_node = false;

		// text nodes
		if (xml_node.nodeType == 3) {
			var dom_node = document.createTextNode(xml_node.data);

		// elements
		} else if (xml_node.nodeType == 1) {
			var dom_node = document.createElement(xml_node.nodeName);

			for (var i = 0; i < xml_node.attributes.length; i++) {
				if (xml_node.attributes[i].name == 'class') {
					dom_node.className = xml_node.attributes[i].value;
				}
				dom_node.setAttribute(xml_node.attributes[i].name,
					xml_node.attributes[i].value);
			}

			for (var i = 0; i < xml_node.childNodes.length; i++) {
				var dom_child = this.convertXmlToDom(xml_node.childNodes[i]);
				if (dom_child != false)
					dom_node.appendChild(dom_child);
			}
		}

		return dom_node;
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
})();

