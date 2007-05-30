/**
 * JavaScript sorting of tag list
 *
 * @param list_id string Id of the list element.
 * @param sort_id string Id of the div around the sorting links.
 */
function PinholeSortableTagList(list_id, sort_id)
{
	// the list should have list elements with
	// id = count/modified_date/photo_count

	this.list = document.getElementById(list_id);
	this.sort_options = document.getElementById(sort_id);
	this.tags = Array();

	this.initList();
	this.drawSortLinks();
}

PinholeSortableTagList.prototype.drawSortLinks = function ()
{
	this.sort_options.appendChild(document.createTextNode('Sort By: '));

	// title
	var anchor = document.createElement('a');
	anchor.href = '#';

	YAHOO.util.Event.addListener(anchor, 'click',
		function(e, tag_list)
		{
			YAHOO.util.Event.preventDefault(e);
			tag_list.sortBy('title');
		}, this);

	var text = document.createTextNode('title');
	anchor.appendChild(text);

	this.sort_options.appendChild(anchor);
	this.sort_options.appendChild(document.createTextNode(', '));

	// photos
	var anchor = document.createElement('a');
	anchor.href = '#';

	YAHOO.util.Event.addListener(anchor, 'click',
		function(e, tag_list)
		{
			YAHOO.util.Event.preventDefault(e);
			tag_list.sortBy('photo_count');
		}, this);

	var text = document.createTextNode('photo count');
	anchor.appendChild(text);

	this.sort_options.appendChild(anchor);
	this.sort_options.appendChild(document.createTextNode(', '));

	// modified date
	var anchor = document.createElement('a');
	anchor.href = '#';

	YAHOO.util.Event.addListener(anchor, 'click',
		function(e, tag_list)
		{
			YAHOO.util.Event.preventDefault(e);
			tag_list.sortBy('modified_date');
		}, this);

	var text = document.createTextNode('last modified');
	anchor.appendChild(text);

	this.sort_options.appendChild(anchor);
}

PinholeSortableTagList.prototype.initList = function ()
{
	// each li element has the format:
	// shortname.modified_date.photo_count
	var list_elements = this.list.getElementsByTagName('li');
	
	for (var i = 0; i < list_elements.length; i++) {
		var node = list_elements[i];
		var tag_info = node.id.split('.');
		
		this.tags[i] = new PinholeSortableTagElement(
			node.innerHTML,
			tag_info[0],
			node.getElementsByTagName('a')[0].innerHTML,
			tag_info[1],
			tag_info[2]);
	}
}

PinholeSortableTagList.prototype.sortBy = function (type)
{

	if (type == 'title')
		this.tags.sort(this.sortByTitle);
	else if (type == 'modified_date')
		this.tags.sort(this.sortByModifiedDate);
	else if (type == 'photo_count')
		this.tags.sort(this.sortByPhotoCount);

	var list_elements = this.list.getElementsByTagName('li');

	for (var i = 0; i < list_elements.length; i++)
		list_elements[i].innerHTML = this.tags[i].tag;
}

PinholeSortableTagList.prototype.sortByTitle = function(a, b)
{
	var x = a.title.toLowerCase();
	var y = b.title.toLowerCase();

	if (x == y) {
		x = a.shortname.toLowerCase();
		y = b.shortname.toLowerCase();
	}

	return ((x < y) ? -1 : 1);
}

PinholeSortableTagList.prototype.sortByPhotoCount = function(a, b)
{
	var x = parseInt(a.photo_count);
	var y = parseInt(b.photo_count);

	if (x == y) {
		x = a.shortname.toLowerCase();
		y = b.shortname.toLowerCase();
	}

	return ((x < y) ? 1 : -1);
}

PinholeSortableTagList.prototype.sortByModifiedDate = function(a, b)
{
	var x = parseInt(a.modified_date);
	var y = parseInt(b.modified_date);

	if (x == y) {
		x = a.shortname.toLowerCase();
		y = b.shortname.toLowerCase();
	}	

	return ((x < y) ? -1 : 1);
}

function PinholeSortableTagElement(tag, shortname, title, modified_date, photo_count) {
	this.tag           = tag;
	this.shortname     = shortname;
	this.title         = title;
	this.photo_count   = photo_count;
	this.modified_date = modified_date;
}
