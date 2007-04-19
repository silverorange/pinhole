<?php

require_once 'Swat/Swat.php';

class UploaderTarget
{
	public function __construct()
	{
		$this->processFiles();
		$this->display();
	}

	protected function processFiles()
	{
		foreach ($_FILES as $file) {
		}
	}

	protected function display()
	{
		Swat::printObject($_POST);
		Swat::printObject($_FILES);
		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	protected function getInlineJavaScript()
	{
		$javascript = '';
		foreach ($_FILES as $id => $file)
			$javascript.= sprintf("window.parent.%s_obj.complete();\n", $id);

		return $javascript;
	}
}

$target = new UploaderTarget();

?>
