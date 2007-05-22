<?php

require_once 'Pinhole/PinholeMachineTag.php';
require_once 'NateGoSearch/NateGoSearchQuery.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeSiteTag extends PinholeMachineTag
{
	// {{{ protected properties

	protected $name_space = 'site';

	// }}}
	// {{{ public function isValid()

	public function isValid()
	{
		return ($this->name == 'page' && is_numeric($this->value));
	}

	// }}}
	// {{{ public function getPath()

	public function getPath()
	{
		return null;
	}

	// }}}
	// {{{ public function getPage()

	public function getPage()
	{
		return ($this->name == 'page') ? $this->value : null;
	}

	// }}}
}

?>
