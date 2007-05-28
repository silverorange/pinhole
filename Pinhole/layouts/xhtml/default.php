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
		<?= $this->search_content ?>
		<?= $this->header_content ?>
		<?= $this->navigation_links ?>
	</div><!-- close #hd -->

	<div id="bd">

		<div id="dates"><?= $this->dates ?></div>
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
