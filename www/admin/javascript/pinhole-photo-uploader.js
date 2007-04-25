/**
 * @copyright 2007 silverorange
 */

// {{{ PinholePhotoUploadManager

PinholePhotoUploadManager = {};

PinholePhotoUploadManager.status_client = null;
PinholePhotoUploadManager.clients = [];
PinholePhotoUploadManager.interval_period = 1500; // in milliseconds
PinholePhotoUploadManager.interval = null;
PinholePhotoUploadManager.sequence = 0;
PinholePhotoUploadManager.received_sequence = 0;

PinholePhotoUploadManager.setStatusClient = function(uri)
{
	PinholePhotoUploadManager.status_client = new XML_RPC_Client(uri);
}

PinholePhotoUploadManager.setStatusClient('Photo/UploadStatusServer');

PinholePhotoUploadManager.addClient = function(client)
{
	var first_client = (PinholePhotoUploadManager.clients.length == 0);

	PinholePhotoUploadManager.clients.push(client);

	if (first_client)
		PinholePhotoUploadManager.setInterval();
}

PinholePhotoUploadManager.removeClient = function(client)
{
	for (var i = 0; i < PinholePhotoUploadManager.clients.length; i++) {
		if (PinholePhotoUploadManager.clients[i] === client) {
			PinholePhotoUploadManager.clients.splice(i, 1);
			break;
		}
	}

	if (PinholePhotoUploadManager.clients.length == 0)
		PinholePhotoUploadManager.clearInterval();
}

PinholePhotoUploadManager.getClient = function(id)
{
	var client = null;
	for (var i = 0; i < PinholePhotoUploadManager.clients.length; i++) {
		if (PinholePhotoUploadManager.clients[i].id === id) {
			client = PinholePhotoUploadManager.clients[i];
			break;
		}
	}
	return client;
}

PinholePhotoUploadManager.setInterval = function()
{
	if (PinholePhotoUploadManager.interval === null) {
		PinholePhotoUploadManager.interval = window.setInterval(
			PinholePhotoUploadManager.updateStatus,
			PinholePhotoUploadManager.interval_period);
	}
}

PinholePhotoUploadManager.clearInterval = function()
{
	window.clearInterval(PinholePhotoUploadManager.interval);
	PinholePhotoUploadManager.interval = null;
}

PinholePhotoUploadManager.updateStatus = function()
{
	if (PinholePhotoUploadManager.clients.length > 0) {

		var client_map = {};
		var client;
		for (var i = 0; i < PinholePhotoUploadManager.clients.length; i++) {
			client = PinholePhotoUploadManager.clients[i];
			client_map[client.id] = client.getUploadIdentifier();
		}

		PinholePhotoUploadManager.sequence++;

		PinholePhotoUploadManager.status_client.callProcedure('getStatus',
			PinholePhotoUploadManager.statusCallback,
			[PinholePhotoUploadManager.sequence, client_map],
			['int', 'struct']);
	}
}

PinholePhotoUploadManager.statusCallback = function(response)
{
	if (response.sequence > PinholePhotoUploadManager.received_sequence) {
		var client;
		for (client_id in response.statuses) {
			client = PinholePhotoUploadManager.getClient(client_id);
			if (client) {
				if (response.statuses[client_id] === 'none') {
					client.progress();
				} else {
					var percent = response.statuses[client_id].bytes_uploaded /
						response.statuses[client_id].bytes_total;

					client.setStatus(percent);
				}
			}
		}
		PinholePhotoUploadManager.received_sequence = response.sequence;
	}
}

// }}}
// {{{ PinholePhotoUploadClient

PinholePhotoUploadClient = function(id, form_action, progress_bar)
{
	this.id = id;
	this.form_action = form_action;
	this.progress_bar = progress_bar;

	this.progress_bar.pulse_step = 0.10;

	this.input = document.getElementById(this.id + '_identifier');
	this.button = document.getElementById(this.id + '_button');

	this.createIFrame();
	this.uploadCompleteEvent = new YAHOO.util.CustomEvent('upload-complete');

	YAHOO.util.Event.addListener(this.button, 'click', this.upload,
		this, true);
}

PinholePhotoUploadClient.complete_text = 'complete';
PinholePhotoUploadClient.progress_text = '% complete';
PinholePhotoUploadClient.progress_unknown_text = 'uploading ...';

PinholePhotoUploadClient.prototype.progress = function()
{
	this.progress_bar.pulse();
	this.progress_bar.setText(PinholePhotoUploadClient.progress_unknown_text);
}

PinholePhotoUploadClient.prototype.setStatus = function(percent)
{
	var text = Math.round(percent * 100);
	this.progress_bar.setValue(percent);
	this.progress_bar.setText(text + PinholePhotoUploadClient.progress_text);
}

PinholePhotoUploadClient.prototype.complete = function()
{
	this.progress_bar.setValue(1);
	this.progress_bar.setText(PinholePhotoUploadClient.complete_text);
	this.uploadCompleteEvent.fire();
	PinholePhotoUploadManager.removeClient(this);
}

PinholePhotoUploadClient.prototype.upload = function(event)
{
	YAHOO.util.Event.preventDefault(event);
	this.input.form.action = this.form_action;
	this.input.form.target = this.id + '_iframe';
	this.input.form.submit();
	this.progress_bar.setValue(0);
	this.progress_bar.setText(PinholePhotoUploadClient.progress_unknown_text);
	this.showProgressBar();
	PinholePhotoUploadManager.addClient(this);
}

/**
 * Shows the progress bar for this uploader using a smooth animation
 */
PinholePhotoUploadClient.prototype.showProgressBar = function()
{
	var animate_div = this.progress_bar.container;
	animate_div.parentNode.style.display = 'block';
	animate_div.parentNode.style.opacity = '0';
	animate_div.parentNode.style.overflow = 'hidden';
	animate_div.parentNode.style.height = '0';
	animate_div.style.visibility = 'hidden';
	animate_div.style.overflow = 'hidden';
	animate_div.style.display = 'block';
	animate_div.style.height = '';
	var height = animate_div.offsetHeight;
	animate_div.style.height = '0';
	animate_div.style.visibility = 'visible';
	animate_div.parentNode.style.height = '';
	animate_div.parentNode.style.overflow = 'visible';

	var slide_animation = new YAHOO.util.Anim(animate_div,
		{ height: { from: 0, to: height } }, 0.5, YAHOO.util.Easing.easeOut);

	var fade_animation = new YAHOO.util.Anim(animate_div.parentNode,
		{ opacity: { from: 0, to: 1 } }, 0.5);

	slide_animation.onComplete.subscribe(fade_animation.animate,
		fade_animation, true);

	slide_animation.animate();
}

PinholePhotoUploadClient.prototype.createIFrame = function()
{
	this.iframe = document.createElement('iframe');
	this.iframe.name = this.id + '_iframe';
	this.iframe.style.border = '0';
	this.iframe.style.width = '1px';
	this.iframe.style.height = '1px';
	this.button.parentNode.insertBefore(this.iframe, this.button);
}

PinholePhotoUploadClient.prototype.getUploadIdentifier = function()
{
	return this.input.value;
}
