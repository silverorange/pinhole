
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
	autocomplete.highlightClassName = 'pinhole-photo-tag-highlight';
	autocomplete.prehighlightClassName = "pinhole-photo-tag-prehighlight"; 
	//autocomplete.useShadow = true; 
	autocomplete.forceSelection = true;
	autocomplete.formatResult = function(oResultItem, sQuery) { 
		var sMarkup = oResultItem[0] + " (" + oResultItem[1] + ")"; 
		return (sMarkup);
	}

	autocomplete.itemSelectEvent.subscribe(this.addTag, this, true);
}

// }}}
// {{{ addTag()

PinholePhotoTagEntry.prototype.addTag = function(oSelf , elItem , oData)
{
	var shortname = elItem[2][1];
	var title = elItem[2][0];

	var li_tag = document.createElement('li');
	li_tag.id = this.id + '_tag_' + shortname;
	li_tag.innerHTML = title + " (<a href=\"javascript: " + this.id  + "_obj.removeTag('" + shortname + "')\">remove</a>)";

	document.getElementById(this.id + '_list').appendChild(li_tag);

	document.getElementById(this.id).value = '';

	for (i = 0; i < this.oACDS.data.length; i++) {
		if (this.oACDS.data[i][1] == shortname) {
			var element = this.oACDS.data.splice(i, 1);
			this.selected_tag_array.push(element[0]);
		}
	}
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
 * Selected Tags
 *
 * An array of tag values selected in the widget 
 *
 * @var array
 */
PinholePhotoTagEntry.selected_tag_array;

// }}}
