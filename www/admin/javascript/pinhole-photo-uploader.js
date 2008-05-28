/**
 * @copyright 2007 silverorange
 */

// {{{ PinholePhotoUploadManager

PinholePhotoUploadManager = {
};

PinholePhotoUploadManager.status_client = null;
PinholePhotoUploadManager.processor_client = null;
PinholePhotoUploadManager.clients = [];
PinholePhotoUploadManager.interval_period = 1500; // in milliseconds
PinholePhotoUploadManager.interval = null;
PinholePhotoUploadManager.sequence = 0;
PinholePhotoUploadManager.received_sequence = 0;

PinholePhotoUploadManager.setProcessorClient = function(uri)
{
	PinholePhotoUploadManager.processor_client = new XML_RPC_Client(uri);
}

PinholePhotoUploadManager.setProcessorClient('Photo/UploadProcessorServer');

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

					var time = response.statuses[client_id].est_sec;

					client.setStatus(percent, time);
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
	this.uploaded_files = [];
	this.i = 0;

	this.progress_bar.pulse_step = 0.10;

	this.input = document.getElementById(this.id + '_identifier');
	this.button = document.getElementById(this.id + '_button');

	this.createIFrame();

	this.uploadCompleteEvent = new YAHOO.util.CustomEvent('upload-complete');
	this.uploadErrorEvent = new YAHOO.util.CustomEvent('upload-error');
	this.fileProcessedEvent = new YAHOO.util.CustomEvent('file-processed');
	this.fileErrorEvent = new YAHOO.util.CustomEvent('file-error');
	this.processingCompleteEvent = new YAHOO.util.CustomEvent('processing-complete');

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

PinholePhotoUploadClient.prototype.setStatus = function(percent, time)
{
	this.progress_bar.setValue(percent);

	var hours = Math.floor(time / 360);
	var minutes = Math.floor(time / 60) % 60;
	var seconds = time % 60;

	var text = '';
	text += (hours > 0) ? hours + ' hours ' : '';
	text += (minutes > 0) ? minutes + ' minutes ' : '';
	text += seconds + ' seconds left';

	this.progress_bar.setText(text);
}

PinholePhotoUploadClient.prototype.uploadComplete = function(file_objects, error_array)
{
	this.progress_bar.setValue(1);
	this.progress_bar.setText(PinholePhotoUploadClient.complete_text);

	var total_photos = this.getObjectArrayLength(file_objects);
	this.uploadCompleteEvent.fire(total_photos);

	this.uploaded_files = file_objects;

	var hidden = document.createElement('input');
	var button = document.getElementById('submit_button');
	hidden.type = 'hidden';
	hidden.name = 'number_of_photos';
	hidden.value = total_photos;
	button.parentNode.insertBefore(hidden, button);

	if (error_array.length > 0)
		this.uploadErrorEvent.fire(error_array);
	else {
		YAHOO.util.Dom.removeClass(button ,'swat-insensitive');
		button.disabled = '';
		this.processNextFile();
	}


	PinholePhotoUploadManager.removeClient(this);
}

PinholePhotoUploadClient.prototype.upload = function(event)
{
	YAHOO.util.Event.preventDefault(event);

	if (document.getElementById(this.id).value.length > 0) {

		// reset the form
		var container = document.getElementById('photo_processing').childNodes[0];
		for (var i = 0; i < container.childNodes.length; i++) {
			if (node.id == '' || node.id != 'photo_container')
				container.removeChild(node);
		}

		var errors = YAHOO.util.Dom.getElementsByClassName(
			'pinhole-photo-uploader-errors');

		if (errors.length > 0)
			container.removeChild(errors[0]);

		// handle the post
		this.input.form.action = this.form_action;
		this.input.form.target = this.id + '_iframe';
		this.input.form.submit();
		this.progress_bar.setValue(0);
		this.progress_bar.setText(PinholePhotoUploadClient.progress_unknown_text);
		this.showProgressBar();
		PinholePhotoUploadManager.addClient(this);
	}
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
	// TODO: better browser detection
	if (navigator.userAgent.indexOf('MSIE') > 0) {
		var div = document.createElement('div');
		div.style.display = 'inline';
		div.innerHTML = '<iframe name="' + this.id + '_iframe" ' +
			'id="' + this.id + '_iframe" ' +
			'src="about:blank" style="border: 0; width: 0; height: 0;">' +
			'</iframe>';

		this.button.parentNode.insertBefore(div, this.button);
	} else {
		var iframe = document.createElement('iframe');
		iframe.name = this.id + '_iframe';
		iframe.id = this.id + '_iframe';
		iframe.style.border = '0';
		iframe.style.width = '0';
		iframe.style.height = '0';
		this.button.parentNode.insertBefore(iframe, this.button);
	}
}

PinholePhotoUploadClient.prototype.getUploadIdentifier = function()
{
	return this.input.value;
}

PinholePhotoUploadClient.prototype.processNextFile = function()
{
	var that = this;
	var button   = document.getElementById('submit_button');

	function callBack(response)
	{
		if (response.error)
			that.fileErrorEvent.fire(response.error);
		else
			that.fileProcessedEvent.fire(response.processed_filename);

		delete that.uploaded_files[response.filename];

		if (that.getObjectArrayLength(that.uploaded_files) == 0)
			that.processingCompleteEvent.fire();

		//  returns the id so that we can set the timezone correctly
		var hidden = document.createElement('input');
		hidden.type = 'hidden';
		hidden.name = 'photo_id' + i;
		hidden.value = response.id;
		button.parentNode.insertBefore(hidden, button);

		that.processNextFile();
	}

	for (var file in this.uploaded_files) {
		this.i ++;
		var i = this.i;
		PinholePhotoUploadManager.processor_client.callProcedure(
			'processFile', callBack,
			[file, this.uploaded_files[file]]);

		return;
	}
}

PinholePhotoUploadClient.prototype.getObjectArrayLength = function(obj)
{
	var count = 0;
	for (var elem in obj)
		count++;

	return count;
}
