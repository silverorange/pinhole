/**
 * @copyright 2007 silverorange
 */

PinholePhotoUploadPage = function(uploader)
{
	this.photos = [
		'http://gallery.whitelands.com/files/thumb/photo57142.jpg',
		'http://gallery.silverorange.com/files/thumb/photo43982.jpg',
		'http://gallery.whitelands.com/files/thumb/photo56909.jpg',
		'http://gallery.whitelands.com/files/thumb/photo56250.jpg',
		'http://gallery.whitelands.com/files/thumb/photo53999.jpg'
	];
	this.count = 0;

	this.photo_container = document.getElementById('photo_container');
	this.spacer_div = document.createElement('div');

	this.uploader = uploader;

	this.uploader.uploadCompleteEvent.subscribe(this.startDemo, this, true);
}

PinholePhotoUploadPage.fade_duration = 1;
PinholePhotoUploadPage.processing_text = 'Processing photos … ';

PinholePhotoUploadPage.prototype.addPhoto = function()
{
	this.spacer_div.style.width = '0';
	this.spacer_div.style.height = '130px';
	this.spacer_div.style.cssFloat = 'left';
	this.spacer_div.style.margin = '0';
	this.photo_container.insertBefore(this.spacer_div,
		this.photo_container.firstChild);

	var animation = new YAHOO.util.Anim(this.spacer_div, { width: { to: 130 }},
		1, YAHOO.util.Easing.easeOutStrong);

	animation.onComplete.subscribe(this.fadeInPhoto, this, true);
	animation.animate();
}

PinholePhotoUploadPage.prototype.fadeInPhoto = function()
{
	var throbber_div = document.createElement('div');
//	throbber_div.style.backgroundImage = 'url(http://www.webtwenny.com/images/Throbber.gif)';
	throbber_div.style.backgroundPosition = 'center center';
	throbber_div.style.backgroundRepeat = 'no-repeat';
	throbber_div.style.width = '130px';
	throbber_div.style.height = '130px';
	throbber_div.style.margin = '0';
	throbber_div.style.cssFloat = 'left';

	var div = document.createElement('div');
	div.style.opacity = '0';
	div.style.width = '100px';
	div.style.height = '100px';
	div.style.backgroundColor = '#ddd';
	div.style.padding = '10px';
	div.style.margin = '5px';
	div.style.textAlign = 'center';

	var image = document.createElement('img');
	image.src = this.photos[this.count % 5];
	this.count++;

	div.appendChild(image);
	throbber_div.appendChild(div);
	this.photo_container.replaceChild(throbber_div, this.spacer_div);
	var animation = new YAHOO.util.Anim(div, {opacity: { to:  1} }, 
		PinholePhotoUploadPage.fade_duration, YAHOO.util.Easing.easeInStrong);

	animation.animate();
}

PinholePhotoUploadPage.prototype.startDemo = function()
{
	var processing_message = document.createElement('h2');
	var processing_text = document.createTextNode(PinholePhotoUploadPage.processing_text);
	processing_message.appendChild(processing_text);
	this.photo_container.parentNode.insertBefore(processing_message,
		this.photo_container);

	var clear_div = document.createElement('div');
	clear_div.style.clear = 'both';
	this.photo_container.parentNode.appendChild(clear_div);

	this.addPhoto();
	this.interval = window.setInterval(function(obj) { obj.addPhoto() },
		4000, this);
}
