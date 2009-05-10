function PinholeCommentDisplay(id, comment_status, spam)
{
	this.id = id;
	this.comment_status = comment_status;
	this.comment_spam = spam;

	var id_split = id.split('_', 2);
	this.comment_id = (id_split[1]) ? id_split[1] : id_split[0];

	this.initControls();
	this.initConfirmation();
	this.container = document.getElementById(this.id);
	this.status_container = document.getElementById(this.id + '_status');
}

PinholeCommentDisplay.approve_text   = 'Approve';
PinholeCommentDisplay.deny_text      = 'Deny';
PinholeCommentDisplay.publish_text   = 'Publish';
PinholeCommentDisplay.unpublish_text = 'Unpublish';
PinholeCommentDisplay.spam_text      = 'Spam';
PinholeCommentDisplay.not_spam_text  = 'Not Spam';
PinholeCommentDisplay.delete_text    = 'Delete';
PinholeCommentDisplay.cancel_text    = 'Cancel';

PinholeCommentDisplay.status_spam_text        = 'Spam';
PinholeCommentDisplay.status_pending_text     = 'Pending';
PinholeCommentDisplay.status_unpublished_text = 'Unpublished';

PinholeCommentDisplay.delete_confirmation_text = 'Delete comment?';

PinholeCommentDisplay.STATUS_PENDING     = 0;
PinholeCommentDisplay.STATUS_PUBLISHED   = 1;
PinholeCommentDisplay.STATUS_UNPUBLISHED = 2;

PinholeCommentDisplay.xml_rpc_client = new XML_RPC_Client(
	'Comment/AjaxServer');

// {{{ initControls()

PinholeCommentDisplay.prototype.initControls = function()
{
	var controls_div = document.getElementById(this.id + '_controls');

	this.approve_button = document.createElement('input');
	this.approve_button.type = 'button';
	this.approve_button.value = PinholeCommentDisplay.approve_text;
	YAHOO.util.Event.on(this.approve_button, 'click',
		this.publish, this, true);

	this.deny_button = document.createElement('input');
	this.deny_button.type = 'button';
	this.deny_button.value = PinholeCommentDisplay.deny_text;
	YAHOO.util.Event.on(this.deny_button, 'click',
		this.unpublish, this, true);

	this.publish_toggle_button = document.createElement('input');
	this.publish_toggle_button.type = 'button';
	this.publish_toggle_button.value = PinholeCommentDisplay.publish_text;
	YAHOO.util.Event.on(this.publish_toggle_button, 'click',
		this.togglePublished, this, true);

	this.spam_toggle_button = document.createElement('input');
	this.spam_toggle_button.type = 'button';
	this.spam_toggle_button.value = PinholeCommentDisplay.spam_text;
	YAHOO.util.Event.on(this.spam_toggle_button, 'click',
		this.toggleSpam, this, true);

	this.delete_button = document.createElement('input');
	this.delete_button.type = 'button';
	this.delete_button.value = PinholeCommentDisplay.delete_text;
	YAHOO.util.Event.on(this.delete_button, 'click',
		this.confirmDelete, this, true);

	if (this.comment_status == PinholeCommentDisplay.STATUS_PUBLISHED) {
		this.publish_toggle_button.value = PinholeCommentDisplay.unpublish_text;
	}

	if (this.comment_spam) {
		this.spam_toggle_button.value = PinholeCommentDisplay.not_spam_text;
		this.approve_button.style.display = 'none';
		this.deny_button.style.display = 'none';
		this.publish_toggle_button.style.display = 'none';
	} else {
		switch (this.comment_status) {
		case PinholeCommentDisplay.STATUS_PENDING:
			this.publish_toggle_button.style.display = 'none';
			break;

		case PinholeCommentDisplay.STATUS_PUBLISHED:
		case PinholeCommentDisplay.STATUS_UNPUBLISHED:
			this.approve_button.style.display = 'none';
			this.deny_button.style.display = 'none';
			break;
		}
	}

	controls_div.appendChild(this.approve_button);
	controls_div.appendChild(document.createTextNode(' '));
	controls_div.appendChild(this.deny_button);
	controls_div.appendChild(document.createTextNode(' '));
	controls_div.appendChild(this.publish_toggle_button);
	controls_div.appendChild(document.createTextNode(' '));
	controls_div.appendChild(this.spam_toggle_button);
	controls_div.appendChild(document.createTextNode(' '));
	controls_div.appendChild(this.delete_button);
}

// }}}
// {{{ initConfirmation()

PinholeCommentDisplay.prototype.initConfirmation = function()
{
	this.confirmation = document.createElement('div');
	this.confirmation.className = 'pinhole-comment-display-confirmation';
	this.confirmation.style.display = 'none';

	var message_div = document.createElement('div');
	PinholeCommentDisplay.setTextContent(message_div,
		PinholeCommentDisplay.delete_confirmation_text);

	this.confirmation.appendChild(message_div);

	this.confirmation_cancel = document.createElement('input');
	this.confirmation_cancel.type ='button';
	this.confirmation_cancel.value = 'Cancel'; //TODO
	this.confirmation.appendChild(this.confirmation_cancel);
	YAHOO.util.Event.on(this.confirmation_cancel, 'click', this.cancelDelete,
		this, true);

	this.confirmation.appendChild(document.createTextNode(' '));

	this.confirmation_ok = document.createElement('input');
	this.confirmation_ok.type ='button';
	this.confirmation_ok.value = PinholeCommentDisplay.delete_text;
	this.confirmation.appendChild(this.confirmation_ok);
	YAHOO.util.Event.on(this.confirmation_ok, 'click', this.deleteComment,
		this, true);

	this.delete_button.parentNode.appendChild(this.confirmation);
}

// }}}
// {{{ publish()

PinholeCommentDisplay.prototype.publish = function()
{
	this.setSensitivity(false);

	var that = this;
	function callBack(response)
	{
		that.comment_status = PinholeCommentDisplay.STATUS_PUBLISHED;

		that.approve_button.style.display = 'none';
		that.deny_button.style.display = 'none';
		that.publish_toggle_button.style.display = 'inline';
		that.publish_toggle_button.value = PinholeCommentDisplay.unpublish_text;

		YAHOO.util.Dom.removeClass(that.container, 'pinhole-comment-red');
		YAHOO.util.Dom.removeClass(that.container, 'pinhole-comment-yellow');
		YAHOO.util.Dom.addClass(that.container, 'pinhole-comment-green');

		that.updateStatus();
		that.setSensitivity(true);
	}

	PinholeCommentDisplay.xml_rpc_client.callProcedure('publish', callBack,
		[this.comment_id], ['int']);
}

// }}}
// {{{ unpublish()

PinholeCommentDisplay.prototype.unpublish = function()
{
	this.setSensitivity(false);

	var that = this;
	function callBack(response)
	{
		that.comment_status = PinholeCommentDisplay.STATUS_UNPUBLISHED;

		that.approve_button.style.display = 'none';
		that.deny_button.style.display = 'none';
		that.publish_toggle_button.style.display = 'inline';
		that.publish_toggle_button.value = PinholeCommentDisplay.publish_text;

		YAHOO.util.Dom.removeClass(that.container, 'pinhole-comment-green');
		YAHOO.util.Dom.removeClass(that.container, 'pinhole-comment-yellow');
		YAHOO.util.Dom.addClass(that.container, 'pinhole-comment-red');

		that.updateStatus();
		that.setSensitivity(true);
	}

	PinholeCommentDisplay.xml_rpc_client.callProcedure('unpublish', callBack,
		[this.comment_id], ['int']);
}

// }}}
// {{{ togglePublished()

PinholeCommentDisplay.prototype.togglePublished = function()
{
	if (this.comment_status === PinholeCommentDisplay.STATUS_PUBLISHED) {
		this.unpublish();
	} else {
		this.publish();
	}
}

// }}}
// {{{ spam()

PinholeCommentDisplay.prototype.spam = function()
{
	this.setSensitivity(false);

	var that = this;
	function callBack(response)
	{
		that.comment_spam = true;

		that.approve_button.style.display = 'none';
		that.deny_button.style.display = 'none';
		that.publish_toggle_button.style.display = 'none';
		that.spam_toggle_button.value = PinholeCommentDisplay.not_spam_text;

		YAHOO.util.Dom.removeClass(that.container, 'pinhole-comment-green');
		YAHOO.util.Dom.removeClass(that.container, 'pinhole-comment-yellow');
		YAHOO.util.Dom.addClass(that.container, 'pinhole-comment-red');

		that.updateStatus();
		that.setSensitivity(true);
	}

	PinholeCommentDisplay.xml_rpc_client.callProcedure('spam', callBack,
		[this.comment_id], ['int']);
}

// }}}
// {{{ notSpam()

PinholeCommentDisplay.prototype.notSpam = function()
{
	this.setSensitivity(false);

	var that = this;
	function callBack(response)
	{
		that.comment_spam = false;

		that.spam_toggle_button.value = PinholeCommentDisplay.spam_text;

		YAHOO.util.Dom.removeClass(that.container, 'pinhole-comment-red');

		if (that.comment_status == PinholeCommentDisplay.STATUS_PENDING) {
			YAHOO.util.Dom.removeClass(that.container, 'pinhole-comment-green');
			YAHOO.util.Dom.addClass(that.container, 'pinhole-comment-yellow');
			that.approve_button.style.display = 'inline';
			that.deny_button.style.display = 'inline';
		} else {
			that.publish_toggle_button.style.display = 'inline';
			YAHOO.util.Dom.removeClass(that.container, 'pinhole-comment-yellow');
			YAHOO.util.Dom.addClass(that.container, 'pinhole-comment-green');
		}

		that.updateStatus();
		that.setSensitivity(true);
	}

	PinholeCommentDisplay.xml_rpc_client.callProcedure('notSpam', callBack,
		[this.comment_id], ['int']);
}

// }}}
// {{{ toggleSpam()

PinholeCommentDisplay.prototype.toggleSpam = function()
{
	if (this.comment_spam) {
		this.notSpam();
	} else {
		this.spam();
	}
}

// }}}
// {{{ setSensitivity()

PinholeCommentDisplay.prototype.setSensitivity = function(sensitive)
{
	this.approve_button.disabled        = !sensitive;
	this.deny_button.disabled           = !sensitive;
	this.publish_toggle_button.disabled = !sensitive;
	this.spam_toggle_button.disabled    = !sensitive;
	this.delete_button.disabled         = !sensitive;
}

// }}}
// {{{ updateStatus()

PinholeCommentDisplay.prototype.updateStatus = function()
{
	if (this.comment_spam) {
		PinholeCommentDisplay.setTextContent(this.status_container,
			' - ' + PinholeCommentDisplay.status_spam_text);
	} else {
		switch (this.comment_status) {
		case PinholeCommentDisplay.STATUS_UNPUBLISHED:
			PinholeCommentDisplay.setTextContent(this.status_container,
				' - ' + PinholeCommentDisplay.status_unpublished_text);

			break;

		case PinholeCommentDisplay.STATUS_PENDING:
			PinholeCommentDisplay.setTextContent(this.status_container,
				' - ' + PinholeCommentDisplay.status_pending_text);

			break;

		default:
			PinholeCommentDisplay.setTextContent(this.status_container, '');
			break;
		}
	}
}

// }}}
// {{{ deleteComment()

PinholeCommentDisplay.prototype.deleteComment = function()
{
	this.confirmation.style.display = 'none';

	var that = this;
	function callBack(response)
	{
		var attributes = { opacity: { to: 0 } };
		var anim = new YAHOO.util.Anim(that.container, attributes, 0.25,
			YAHOO.util.Easing.easeOut);

		anim.onComplete.subscribe(that.shrink, that, true);
		anim.animate();
	}

	PinholeCommentDisplay.xml_rpc_client.callProcedure('delete', callBack,
		[this.comment_id], ['int']);
}

// }}}
// {{{ shrink()

PinholeCommentDisplay.prototype.shrink = function()
{
	var anim = new YAHOO.util.Anim(this.container, { height: { to: 0 } },
		0.3, YAHOO.util.Easing.easeInStrong);

	anim.onComplete.subscribe(this.removeContainer, this, true);
	anim.animate();
}

// }}}
// {{{ removeContainer()

PinholeCommentDisplay.prototype.removeContainer = function()
{
	YAHOO.util.Event.purgeElement(this.container, true);
	this.container.parentNode.removeChild(this.container);
	delete this.container;
}

// }}}
// {{{ confirmDelete()

PinholeCommentDisplay.prototype.confirmDelete = function()
{
	this.setSensitivity(false);

	var parent_region = YAHOO.util.Dom.getRegion(this.delete_button);

	this.confirmation.style.display = 'block';

	var region = YAHOO.util.Dom.getRegion(this.confirmation);
	YAHOO.util.Dom.setXY(this.confirmation,
		[parent_region.right - (region.right - region.left),
		parent_region.top]);

	this.confirmation_cancel.focus();
}

// }}}
// {{{ cancelDelete()

PinholeCommentDisplay.prototype.cancelDelete = function()
{
	this.confirmation.style.display = 'none';
	this.setSensitivity(true);
}

// }}}
// {{{ static setTextContent()

PinholeCommentDisplay.setTextContent = function(element, text)
{
	if (element.innerText) {
		element.innerText = text;
	} else {
		element.textContent = text;
	}
}

// }}}
