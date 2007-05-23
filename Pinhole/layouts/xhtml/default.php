<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<? include 'common/htmlhead.php' ?>

<body>

<div id="<?=$this->yui_grid_id?>" class="<?=$this->yui_grid_class?>">

	<div id="hd">
		<h1>
			<a href="." title="Home Page" accesskey="1">Gallery</a>
			<? /* TODO: make this gallery name dynamic  */ ?>
		</h1>
		<form id="search"><? /* TODO: Make this search actually work */ ?>
			<div>
				<input type="radio" name="search_scope" id="all_photos" />
				<label for="all_photos">All Photos</label>
				<input type="radio" name="search_scope" id="filtered_photos" />
				<label for="filtered_photos">This Set</label>
				<input type="text" />
				<input type="submit" value="Search" />
			</div>
		</form>
		<?= $this->header_content ?>
	</div><!-- close #hd -->

	<div id="bd">

		<div id="yui-main">
		<div class="yui-b">
			<div id="content">
			<h2 id="page_title"><?= $this->title ?></h2>
			<?= $this->content ?>
			</div>
		</div>
		</div>

		<div class="yui-b">
			<div id="sidebar"><?= $this->sidebar_content ?></div>
		</div>

	</div><!-- close #bd -->

	<div id="ft">Powered by Pinhole</div>

</div>

</body>
</html>
