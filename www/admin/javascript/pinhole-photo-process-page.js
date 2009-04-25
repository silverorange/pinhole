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

	var Dom     = YAHOO.util.Dom;
	var Event   = YAHOO.util.Event;
	var Anim    = YAHOO.util.Anim;
	var Connect = YAHOO.util.Connect;

	Pinhole.page.ProcessPhotosPage = function(unprocessed_photos)
	{
		if (!unprocessed_photos)
			unprocessed_photos = [];

		// hide manual process button
		YAHOO.util.Event.onAvailable('manual_process',
			function () {
				document.getElementById('manual_process').style.display = 'none';
			}
		, this);

		this.unprocessed_photos = unprocessed_photos;
		this.total_count = unprocessed_photos.length;
		this.processing_message = document.getElementById('processing_message');
		this.processing_errors = document.getElementById('processing_errors');

		// for display
		this.current_photo = null;
		this.spacer_div = document.createElement('div');
		this.container = document.getElementById('processing_div');

		this.updateMessage();

		if (this.unprocessed_photos.length > 0) {
			this.processPhoto(this.unprocessed_photos.shift());
		}
	};

	var proto = Pinhole.page.ProcessPhotosPage.prototype;

	Pinhole.page.ProcessPhotosPage.fade_duration = 1;
	Pinhole.page.ProcessPhotosPage.thumbnail_width = 100;
	Pinhole.page.ProcessPhotosPage.thumbnail_height = 100;
	Pinhole.page.ProcessPhotosPage.thumbnail_margin = 5;
	Pinhole.page.ProcessPhotosPage.thumbnail_padding = 10;

	proto.processPhoto = function(id)
	{
		this.current_photo = id;

		var self = this;

		function callBack(response) {
			self.updateMessage();

			if (response.status == 'processed')
				self.displayPhoto(response.image_uri);
			else
				self.displayError(response.error_message);

			if (self.unprocessed_photos.length > 0)
				self.processPhoto(self.unprocessed_photos.shift());
		}

		var client = new XML_RPC_Client('Photo/ProcessorServer');
		client.callProcedure('processPhoto', callBack, [id], 'int');

	}

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

	proto.displayError = function(error_message)
	{
		var div = document.createElement('div');
		div.className = 'processing-error';
		div.innerHTML = error_message;
		this.processing_errors.appendChild(div);
	}

	proto.displayPhoto = function(image_uri)
	{
		this.current_photo_path = '../' + image_uri;

		this.spacer_div.style.width = '0';
		this.spacer_div.style.height =
			(Pinhole.page.ProcessPhotosPage.thumbnail_height +
			Pinhole.page.ProcessPhotosPage.thumbnail_margin * 2 +
			Pinhole.page.ProcessPhotosPage.thumbnail_padding * 2) + 'px';

		this.spacer_div.style.cssFloat = 'left';
		this.spacer_div.style.margin = '0';
		this.container.insertBefore(this.spacer_div,
			this.container.firstChild);

		var animation = new YAHOO.util.Anim(this.spacer_div,
			{ width: { to:
				Pinhole.page.ProcessPhotosPage.thumbnail_width +
				Pinhole.page.ProcessPhotosPage.thumbnail_margin * 2 +
				Pinhole.page.ProcessPhotosPage.thumbnail_padding * 2 } },
			1, YAHOO.util.Easing.easeOutStrong);

		animation.onComplete.subscribe(this.fadeInPhoto, this, true);
		animation.animate();
	}

	proto.fadeInPhoto = function()
	{
		var throbber_div = document.createElement('div');
		throbber_div.style.backgroundPosition = 'center center';
		throbber_div.style.backgroundRepeat = 'no-repeat';
		throbber_div.style.width =
			(Pinhole.page.ProcessPhotosPage.thumbnail_width +
			Pinhole.page.ProcessPhotosPage.thumbnail_margin * 2 +
			Pinhole.page.ProcessPhotosPage.thumbnail_padding * 2) + 'px';

		throbber_div.style.height =
			(Pinhole.page.ProcessPhotosPage.thumbnail_height +
			Pinhole.page.ProcessPhotosPage.thumbnail_margin * 2 +
			Pinhole.page.ProcessPhotosPage.thumbnail_padding * 2) + 'px';

		throbber_div.style.margin = '0';
		throbber_div.style.cssFloat = 'left';

		var div = document.createElement('div');
		div.style.opacity = '0';
		div.style.width = Pinhole.page.ProcessPhotosPage.thumbnail_width + 'px';
		div.style.height = Pinhole.page.ProcessPhotosPage.thumbnail_height + 'px';
		div.style.backgroundColor = '#ddd';
		div.style.padding = Pinhole.page.ProcessPhotosPage.thumbnail_padding + 'px';
		div.style.margin = Pinhole.page.ProcessPhotosPage.thumbnail_margin + 'px';
		div.style.textAlign = 'center';

		var image = document.createElement('img');
		image.src = this.current_photo_path;

		div.appendChild(image);
		throbber_div.appendChild(div);

		this.container.replaceChild(throbber_div, this.spacer_div);
		var animation = new YAHOO.util.Anim(div, {opacity: { to:  1} },
			Pinhole.page.ProcessPhotosPage.fade_duration,
			YAHOO.util.Easing.easeInStrong);

		animation.animate();
	}

// }}}
})();

