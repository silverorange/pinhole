/**
 * @copyright 2007 silverorange
 */

PinholePhotoUploadPage = function(uploader)
{
	this.photo_container = document.getElementById('photo_container');
	this.spacer_div = document.createElement('div');
	this.processing_message = document.createElement('h2');
	this.error_messages = document.createElement('ul');
	this.error_messages.className = 'pinhole-photo-uploader-errors';

	this.uploader = uploader;
	this.total_photos;
	this.errors = [];

	this.current_photo_path = null;

	this.uploader.uploadCompleteEvent.subscribe(this.display, this, true);
	this.uploader.uploadErrorEvent.subscribe(this.uploadError, this, true);
	this.uploader.fileProcessedEvent.subscribe(this.addPhoto, this, true);
	this.uploader.fileErrorEvent.subscribe(this.addError, this, true);
	this.uploader.processingCompleteEvent.subscribe(this.complete, this, true);
}

PinholePhotoUploadPage.fade_duration = 1;
PinholePhotoUploadPage.thumbnail_width = 100;
PinholePhotoUploadPage.thumbnail_height = 100;
PinholePhotoUploadPage.thumbnail_margin = 5;
PinholePhotoUploadPage.thumbnail_padding = 10;
PinholePhotoUploadPage.path = '../images/photos/thumb';
PinholePhotoUploadPage.processing_text = 'Processing Photo … ';
PinholePhotoUploadPage.processed_text = 'Finished Processing!';

PinholePhotoUploadPage.prototype.addPhoto = function(type, args)
{
	this.current_photo_path =
		PinholePhotoUploadPage.path + '/' + args[0] + '.jpg';

	this.spacer_div.style.width = '0';
	this.spacer_div.style.height =
		(PinholePhotoUploadPage.thumbnail_height +
		PinholePhotoUploadPage.thumbnail_margin * 2 +
		PinholePhotoUploadPage.thumbnail_padding * 2) + 'px';

	this.spacer_div.style.cssFloat = 'left';
	this.spacer_div.style.margin = '0';
	this.photo_container.insertBefore(this.spacer_div,
		this.photo_container.firstChild);

	var animation = new YAHOO.util.Anim(this.spacer_div,
		{ width: { to:
			PinholePhotoUploadPage.thumbnail_width +
			PinholePhotoUploadPage.thumbnail_margin * 2 +
			PinholePhotoUploadPage.thumbnail_padding * 2 } },
		1, YAHOO.util.Easing.easeOutStrong);

	animation.onComplete.subscribe(this.fadeInPhoto, this, true);
	animation.animate();

	this.current_photo++;
	this.updateProcessingText();
}

PinholePhotoUploadPage.prototype.addError  = function(type, args)
{
	this.current_photo++;
	this.updateProcessingText();

	this.errors.push(args[0]);
}

PinholePhotoUploadPage.prototype.uploadError  = function(type, args)
{
	this.errors.push(args[0]);
	this.complete();
}

PinholePhotoUploadPage.prototype.fadeInPhoto = function()
{
	var throbber_div = document.createElement('div');
	//throbber_div.style.backgroundImage = 'url(http://www.webtwenny.com/images/Throbber.gif)';
	throbber_div.style.backgroundPosition = 'center center';
	throbber_div.style.backgroundRepeat = 'no-repeat';
	throbber_div.style.width =
		(PinholePhotoUploadPage.thumbnail_width +
		PinholePhotoUploadPage.thumbnail_margin * 2 +
		PinholePhotoUploadPage.thumbnail_padding * 2) + 'px';

	throbber_div.style.height =
		(PinholePhotoUploadPage.thumbnail_height +
		PinholePhotoUploadPage.thumbnail_margin * 2 +
		PinholePhotoUploadPage.thumbnail_padding * 2) + 'px';

	throbber_div.style.margin = '0';
	throbber_div.style.cssFloat = 'left';

	var div = document.createElement('div');
	div.style.opacity = '0';
	div.style.width = PinholePhotoUploadPage.thumbnail_width + 'px';
	div.style.height = PinholePhotoUploadPage.thumbnail_height + 'px';
	div.style.backgroundColor = '#ddd';
	div.style.padding = PinholePhotoUploadPage.thumbnail_padding + 'px';
	div.style.margin = PinholePhotoUploadPage.thumbnail_margin + 'px';
	div.style.textAlign = 'center';

	var image = document.createElement('img');
	image.src = this.current_photo_path;

	div.appendChild(image);
	throbber_div.appendChild(div);

	this.photo_container.replaceChild(throbber_div, this.spacer_div);
	var animation = new YAHOO.util.Anim(div, {opacity: { to:  1} },
		PinholePhotoUploadPage.fade_duration, YAHOO.util.Easing.easeInStrong);

	animation.animate();
}

PinholePhotoUploadPage.prototype.display = function(type, args)
{
	this.total_photos = args[0];
	this.current_photo = 1;

	this.updateProcessingText();

	this.photo_container.parentNode.insertBefore(this.processing_message,
		this.photo_container);

	var clear_div = document.createElement('div');
	clear_div.style.clear = 'both';
	this.photo_container.parentNode.appendChild(clear_div);
}

PinholePhotoUploadPage.prototype.updateProcessingText = function(count)
{
	var text = PinholePhotoUploadPage.processing_text;
	//text+= ' ' + this.current_photo + ' of ' + this.total_photos;
	this.processing_message.innerHTML = text;
}

PinholePhotoUploadPage.prototype.complete = function()
{
	this.processing_message.innerHTML =
		PinholePhotoUploadPage.processed_text;

	if (this.errors.length) {
		this.photo_container.parentNode.insertBefore(this.error_messages,
			this.photo_container);

		for (i = 0; i < this.errors.length; i++) {
			var error = document.createElement('li');
			error.innerHTML = this.errors[0];
			this.error_messages.appendChild(error);
		}
	}
}
