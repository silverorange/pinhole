<?php

require_once 'Pinhole/exceptions/PinholeException.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
abstract class PinholeMachineTag
{
	// {{{ protected properties

	protected $name_space;
	protected $db = null;
	protected $name;
	protected $value;

	// }}}
	// {{{ public function __construct()

	public function __construct($db, $name, $value)
	{
		if ($this->name_space === null)
			throw new PinholeException('A machine tag must
				have a name space defined');

		$this->db = $db;
		$this->name = $name;
		$this->value = $value;
	}

	// }}}
	// {{{ public function isValid()

	public function isValid()
	{
		return true;
	}

	// }}}
	// {{{ public function getPath()

	public function getPath()
	{
		return sprintf('%s.%s=%d',
			$this->name_space,
			$this->name,
			$this->value);
	}

	// }}}
	// {{{ abstract public function getTitle()

	abstract public function getTitle();

	// }}}
	// {{{ abstract public function getWhereClause()

	abstract public function getWhereClause();

	// }}}
}

?>
