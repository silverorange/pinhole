<?php

/**
 * @package   Pinhole
 * @copyright 2017 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeGeoTagSearchTemplate extends SiteAbstractTemplate
{
	// {{{ public function display()

	public function display(SiteLayoutData $data)
	{
		echo <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>{$data->title}</title>
	<base href="{$data->basehref}"></base>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	{$data->html_head_entries}
</head>
<body>

<div id="search_content" style="margin: 1em;">
{$data->content}
</div>

</body>
</html>

HTML;
	}

	// }}}
}

?>
