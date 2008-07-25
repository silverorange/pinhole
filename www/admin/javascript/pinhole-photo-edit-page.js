function PinholePhotoEditPage()
{
	this.private_checkbox = document.getElementById('private');
	this.passphrase = document.getElementById('passphrase_field');

	if (!this.passphrase)
		return;

	this.togglePassphrase();

	YAHOO.util.Event.addListener(this.private_checkbox, 'click',
		this.togglePassphrase, this, true);
}

PinholePhotoEditPage.prototype.togglePassphrase = function(e)
{
	if (this.private_checkbox.checked) {
		this.passphrase.style.display = 'block';
	} else {
		this.passphrase.style.display = 'none';
	}
}

