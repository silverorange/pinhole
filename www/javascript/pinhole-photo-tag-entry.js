
/**
 * A widget for choosing tags in the photo editor
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */

// {{{ PinholePhotoTagEntry()

/**
 * Creates a new tag entry widget
 *
 * @param string id the unique identifier of this entry object.
 */
function PinholePhotoTagEntry(id)
{
	this.id = id;
	this.selected_tag_array = new Array();

	YAHOO.util.Event.onContentReady(
		this.id, this.handleOnAvailable, this, true);
}

// }}}
// {{{ handleOnAvailable()

/**
 * Sets up the cropping widget when the cropper is available and loaded in the
 * DOM tree
 */
PinholePhotoTagEntry.prototype.handleOnAvailable = function()
{
	this.oACDS = new YAHOO.widget.DS_JSArray(this.tag_array);
 
	// Instantiate AutoComplete 
	autocomplete = new YAHOO.widget.AutoComplete(this.id, this.id + '_container', this.oACDS);
	autocomplete.queryDelay = 0; 
	autocomplete.minQueryLength = 0; 
	autocomplete.highlightClassName = 'pinhole-photo-tag-highlight';
	autocomplete.prehighlightClassName = "pinhole-photo-tag-prehighlight"; 
	//autocomplete.useShadow = true; 
	autocomplete.forceSelection = true;
	autocomplete.formatResult = function(oResultItem, sQuery) { 
		var sMarkup = oResultItem[0] + " (" + oResultItem[1] + ")"; 
		return (sMarkup);
	}

	autocomplete.itemSelectEvent.subscribe(
		this.addTagFromAutoComplete, this, true);

	// init values passed in
	if (this.value_array.length > 0)
		for (var i = 0; i < this.value_array.length; i++)
			this.addTag(this.value_array[i]);
}

// }}}
// {{{ addTagFromAutoComplete()

PinholePhotoTagEntry.prototype.addTagFromAutoComplete = function(oSelf , elItem , oData)
{
	var shortname = elItem[2][1];
	this.addTag(shortname);
}

// }}}
// {{{ addTag()

PinholePhotoTagEntry.prototype.addTag = function(shortname)
{
	var title = null;

	for (i = 0; i < this.oACDS.data.length; i++) {
		if (this.oACDS.data[i][1] == shortname) {
			title = this.oACDS.data[i][0];
			var element = this.oACDS.data.splice(i, 1);
			this.selected_tag_array.push(element[0]);
		}
	}

	if (title == null)
		return;

	var li_tag = document.createElement('li');
	li_tag.id = this.id + '_tag_' + shortname;
	li_tag.innerHTML = title + " (<a href=\"javascript: " + this.id  + "_obj.removeTag('" + shortname + "')\">remove</a>)";

	var hidden_tag = document.createElement('input');
	hidden_tag.type = 'hidden';
	hidden_tag.name = this.id + '[]';
	hidden_tag.value = shortname;

	li_tag.appendChild(hidden_tag);
	document.getElementById(this.id + '_list').appendChild(li_tag);

	// clear input value once a value is chosen
	document.getElementById(this.id).value = '';
}

// }}}
// {{{ removeTag()

PinholePhotoTagEntry.prototype.removeTag = function(shortname)
{
	var li_tag = document.getElementById(this.id + '_tag_' + shortname);
	document.getElementById(this.id + '_list').removeChild(li_tag);	

	for (i = 0; i < this.selected_tag_array.length; i++) {
		if (this.selected_tag_array[i][1] == shortname) {
			var element = this.selected_tag_array.splice(i, 1);
			this.oACDS.data.push(element[0]);
			this.oACDS.data.sort();
			break;
		}
	}
}

// }}}
// {{{ static properties

/**
 * Tags
 *
 * An array of tag values to use for autocomplete 
 *
 * @var array
 */
PinholePhotoTagEntry.tag_array;

/**
 * Values
 *
 * An array of tags that are added at startup 
 *
 * @var array
 */
PinholePhotoTagEntry.value_array;

/**
 * Selected Tags
 *
 * An array of tag values selected in the widget 
 *
 * @var array
 */
PinholePhotoTagEntry.selected_tag_array;

// }}}
