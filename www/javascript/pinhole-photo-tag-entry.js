/**
 * Control for selecting multiple tags from a list of tags
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
 * @param array tag_list an array of tag strings and titles that are possible
 *                        to select using this control
 * @param array initial_selected_tag_list an array of already selected tag
 *                                         strings.
 */
function PinholePhotoTagEntry(id, tag_list, initial_selected_tag_list)
{
	this.id = id;
	this.tag_list = tag_list;
	this.initial_selected_tag_list = initial_selected_tag_list;
	this.selected_tag_list = [];
	this.input_element = document.getElementById(this.id + '_value');
	this.data_store = new YAHOO.widget.DS_JSArray(this.tag_list);
	this.list_element = document.getElementById(this.id + '_list');

	YAHOO.util.Event.onContentReady(
		this.id + '_value', this.handleOnAvailable, this, true);
}

// }}}
// {{{ handleOnAvailable()

/**
 * Sets up the auto-complete widget used by this tag selection control
 */
PinholePhotoTagEntry.prototype.handleOnAvailable = function()
{
	// create auto-complete widget
	var auto_complete = new YAHOO.widget.AutoComplete(
		this.input_element, this.id + '_container', this.data_store, {
		queryDelay:            0,
		minQueryLength:        0,
		highlightClassName:    'pinhole-photo-tag-highlight',
		prehighlightClassName: 'pinhole-photo-tag-prehighlight',
		useShadow:             false,
		forceSelection:        true,
		animVert:              false,
		formatResult:
			function(item, query)
			{
				// 0 is title, 1 is tag string
				return item[0] + ' (' + item[1] + ')'; 
			}
	});

	auto_complete.itemSelectEvent.subscribe(
		this.addTagFromAutoComplete, this, true);

	// initialize values passed in
	for (var i = 0; i < this.initial_selected_tag_list.length; i++)
		this.addTag(this.initial_selected_tag_list[i]);
}

// }}}
// {{{ addTagFromAutoComplete()

PinholePhotoTagEntry.prototype.addTagFromAutoComplete = function(
	oSelf, elItem, oData)
{
	var tag_string = elItem[2][1];
	this.addTag(tag_string);
}

// }}}
// {{{ addTag()

PinholePhotoTagEntry.prototype.addTag = function(tag_string)
{
	var found = false;

	for (i = 0; i < this.data_store.data.length; i++) {
		if (this.data_store.data[i][1] == tag_string) {
			// get tag title
			var title = this.data_store.data[i][0];

			// remove row from data store
			var element = this.data_store.data.splice(i, 1);

			// add row to list of selected tags, splice returns an array
			this.selected_tag_list.push(element[0]);

			found = true;
			break;
		}
	}

	if (!found)
		return;

	// create new list node
	var li_tag = document.createElement('li');
	li_tag.id = this.id + '_tag_' + tag_string;

	var title_node = document.createTextNode(title + ' ');

	var anchor_tag = document.createElement('a');
	anchor_tag.id = this.id + '_tag_remove_' + tag_string;
	anchor_tag.href = '#';
	anchor_tag.appendChild(document.createTextNode(
		PinholePhotoTagEntry.remove_text));

	YAHOO.util.Event.addListener(anchor_tag, 'click',
		function (e)
		{
			YAHOO.util.Event.preventDefault(e);
			this.removeTag(tag_string);
		},
		this, true);

	var hidden_tag = document.createElement('input');
	hidden_tag.type = 'hidden';
	hidden_tag.name = this.id + '[]';
	hidden_tag.value = tag_string;

	li_tag.appendChild(title_node);
	li_tag.appendChild(anchor_tag);
	li_tag.appendChild(hidden_tag);

	// add list node
	this.list_element.appendChild(li_tag);

	// clear input value once a value is chosen
	this.input_element.value = '';
}

// }}}
// {{{ removeTag()

PinholePhotoTagEntry.prototype.removeTag = function(tag_string)
{
	// remove event listener
	var anchor_tag = document.getElementById(
		this.id + '_tag_remove_' + tag_string);

	if (anchor_tag)
		YAHOO.util.Event.purgeElement(anchor_tag);

	// remove list node 
	var li_tag = document.getElementById(this.id + '_tag_' + tag_string);
	if (li_tag)
		li_tag.parentNode.removeChild(li_tag);

	for (i = 0; i < this.selected_tag_list.length; i++) {
		if (this.selected_tag_list[i][1] == tag_string) {
			// remove row from selected list
			var element = this.selected_tag_list.splice(i, 1);

			// add row back to data store, splice returns an array
			this.data_store.data.push(element[0]);
			this.data_store.data.sort();
			break;
		}
	}
}

// }}}
// {{{ static properties

/**
 * Remove string resource
 *
 * @var string
 */
PinholePhotoTagEntry.remove_text = 'remove';

// }}}
