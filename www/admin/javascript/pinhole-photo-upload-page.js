/**
 * @copyright 2007 silverorange
 */

PinholePhotoUploadPage = function()
{
	this.photo_container = document.getElementById('photo_container');
	var processing_message = document.createElement('h2');
	var processing_text = document.createTextNode(PinholePhotoUploadPage.processing_text);
	processing_message.appendChild(processing_text);
	this.photo_container.parentNode.insertBefore(processing_message, this.photo_container);

	var clear_div = document.createElement('div');
	clear_div.style.clear = 'both';
	this.photo_container.parentNode.appendChild(clear_div);

	this.interval = window.setInterval(function(obj) { obj.addPhoto() }, 4000, this);
}

PinholePhotoUploadPage.fade_duration = 2;
PinholePhotoUploadPage.processing_text = 'Processing photos … ';

PinholePhotoUploadPage.prototype.addPhoto = function()
{
	var div = document.createElement('div');
	div.style.opacity = '0';
	div.style.cssFloat = 'left';
	div.style.width = '100px';
	div.style.height = '100px';
	div.style.backgroundColor = '#ddd';
	div.style.padding = '1em';
	div.style.margin = '0.5em';

	var image = document.createElement('img');
	image.src = 'http://gallery.silverorange.com/files/thumb/photo43982.jpg';

	div.appendChild(image);
	this.photo_container.insertBefore(div, this.photo_container.firstChild);
	var animation = new YAHOO.util.Anim(div, {opacity: { to:  1} }, 
		PinholePhotoUploadPage.fade_duration, YAHOO.util.Easing.easeIn);

	animation.animate();
}
