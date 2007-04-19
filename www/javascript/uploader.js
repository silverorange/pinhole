/**
 * @copyright 2007 silverorange
 * @todo      add sequence numbers to ensure we never get inaccurate status.
 */

UploadManager = {};

UploadManager.status_client = null;
UploadManager.clients = [];
UploadManager.client_upload_ids = [];
UploadManager.interval_period = 1000; // in milliseconds
UploadManager.interval = null;

UploadManager.setStatusClient = function(uri)
{
	UploadManager.status_client = new XML_RPC_Client(uri);
}

UploadManager.setStatusClient('UploaderStatusServer.php');

UploadManager.addClient = function(client)
{
	var first_client = (UploadManager.clients.length == 0);

	UploadManager.client_upload_ids.push(client.getUploadIdentifier());
	UploadManager.clients.push(client);

	if (first_client)
		UploadManager.setInterval();
}

UploadManager.removeClient = function(client)
{
	for (var i = 0; i < UploadManager.clients.length; i++) {
		if (UploadManager.clients[i] === client) {
			UploadManager.clients.splice(i, 1);
			UploadManager.client_upload_ids.splice(i, 1);
			break;
		}
	}

	if (UploadManager.clients.length == 0)
		UploadManager.clearInterval();
}

UploadManager.setInterval = function()
{
	if (UploadManager.interval === null) {
		UploadManager.interval = window.setInterval(
			UploadManager.updateStatus, UploadManager.interval_period);
	}
}

UploadManager.clearInterval = function()
{
	window.clearInterval(UploadManager.interval);
	UploadManager.interval = null;
}

UploadManager.updateStatus = function()
{
	if (UploadManager.clients.length > 0) {
		UploadManager.status_client.callProcedure('getStatus',
			UploadManager.statusCallback,
			[UploadManager.client_upload_ids], ['array']);
	}
}

UploadManager.statusCallback = function(statuses)
{
	var client;
	for (var i = 0; i < statuses.length; i++) {
		client = UploadManager.clients[i];
		if (statuses[i] === false) {
			client.complete();
		} else if (statuses[i] === true) {
			client.progress();
		} else {
			var percent = statuses[i].bytes_uploaded / statuses[i].bytes_total;
			client.setStatus(percent);
		}
	}
}

UploadClient = function(id, form_action, progress_bar)
{
	this.id = id;
	this.form_action = form_action;
	this.progress_bar = progress_bar;

	this.progress_bar.pulse_step = 0.10;

	this.input = document.getElementById(this.id + '_identifier');
	this.button = document.getElementById(this.id + '_button');

	this.createIFrame();

	YAHOO.util.Event.addListener(this.button, 'click', this.upload,
		this, true);
}

UploadClient.complete_text = 'complete';
UploadClient.progress_text = '% complete';
UploadClient.progress_unknown_text = 'uploading ...';

UploadClient.prototype.progress = function()
{
	this.progress_bar.pulse();
	this.progress_bar.setText(UploadClient.progress_unknown_text);
}

UploadClient.prototype.setStatus = function(percent)
{
	var text = Math.round(percent * 100);
	this.progress_bar.setValue(percent);
	this.progress_bar.setText(text + UploadClient.progress_text);
}

UploadClient.prototype.complete = function()
{
	this.progress_bar.setValue(1);
	this.progress_bar.setText(UploadClient.complete_text);
	UploadManager.removeClient(this);
}

UploadClient.prototype.upload = function(event)
{
	YAHOO.util.Event.preventDefault(event);
	this.input.form.action = this.form_action;
	this.input.form.target = this.id + '_iframe';
	this.input.form.submit();
	this.progress_bar.setValue(0);
	this.progress_bar.setText(UploadClient.progress_unknown_text);
	this.showProgressBar();
	UploadManager.addClient(this);
}

/**
 * Shows the progress bar for this uploader using a smooth animation
 */
UploadClient.prototype.showProgressBar = function()
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

UploadClient.prototype.createIFrame = function()
{
	this.iframe = document.createElement('iframe');
	this.iframe.name = this.id + '_iframe';
	this.iframe.style.border = '0';
	this.iframe.style.width = '1px';
	this.iframe.style.height = '1px';
	this.button.parentNode.insertBefore(this.iframe, this.button);
}

UploadClient.prototype.getUploadIdentifier = function()
{
	return this.input.value;
}
