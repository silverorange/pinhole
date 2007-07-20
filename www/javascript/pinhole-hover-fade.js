HoverFade = function(id)
{
	this.id = id;
	this.container = document.getElementById(id);
	this.elements = [];
	for (var i = 0; i < this.container.childNodes.length; i++)
		if (this.container.childNodes[i].nodeType === 1)
			this.elements.push(new HoverFadeElement(
				this.container.childNodes[i]));
}

HoverFadeElement = function(element)
{
	this.element = element;
 
	HoverFadeElement.border_color = YAHOO.util.Dom.getStyle(element, 'borderColor');
	HoverFadeElement.background_color = YAHOO.util.Dom.getStyle(element, 'backgroundColor');

	if (HoverFadeElement.border_color == '' || 'transparent')
		HoverFadeElement.border_color = '#666666';

	if (HoverFadeElement.background_color == '' || 'transparent')
		HoverFadeElement.background_color = '#333';

	this.animation = new YAHOO.util.ColorAnim(this.element,
		{ borderColor: { from: HoverFadeElement.border_color, to: HoverFadeElement.background_color } },
		0.5, YAHOO.util.Easing.easeOut);

	YAHOO.util.Event.addListener(this.element, 'mouseover',
		this.handleMouseOver, this, true);

	YAHOO.util.Event.addListener(this.element, 'mouseout',
		this.handleMouseOut, this, true);
}

HoverFadeElement.prototype.handleMouseOver = function(e)
{
	// make sure related target is not contained in element and check if
	// we moved inside from outside the main window
	var related_target = YAHOO.util.Event.getRelatedTarget(e);
	if (related_target !== null && related_target !== undefined)
		while (related_target !== this.element && related_target.parentNode &&
			related_target.nodeName !== 'HTML')
			related_target = related_target.parentNode;

	if (related_target === null || related_target === undefined ||
		related_target !== this.element) {
		if (this.animation.isAnimated())
			this.animation.stop();

		this.element.style.borderColor = HoverFadeElement.border_color;
	}
}

HoverFadeElement.prototype.handleMouseOut = function(e)
{
	// make sure related target is not contained in element and check if
	// we moved outside the main window
	var related_target = YAHOO.util.Event.getRelatedTarget(e);
	if (related_target !== null && related_target !== undefined)
		while (related_target !== this.element && related_target.parentNode &&
			related_target.nodeName !== 'HTML')
			related_target = related_target.parentNode;

	if (related_target === null || related_target === undefined ||
		related_target !== this.element) 
		this.animation.animate();
}
