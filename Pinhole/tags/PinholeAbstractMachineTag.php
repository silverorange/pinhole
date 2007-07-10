<?php

require_once 'Pinhole/tags/PinholeAbstractTag.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class PinholeAbstractMachineTag extends PinholeAbstractTag
{
	const SYNTAX_PATTERN = '/^([a-z]+)\.([a-z]+)=([a-zA-Z0-9-\+]*)$/';

	public function __toString()
	{
		return sprintf('%s.%s=%s',
			$this->getNamespace(), $this->getName(), $this->getValue());
	}

	abstract protected function getNamespace();
	abstract protected function getName();
	abstract protected function getValue();

	protected final function getParts($string)
	{
		$parts = array();

		$matches = array();
		if (preg_match(self::SYNTAX_PATTERN, $string, $matches) == 1) {
			$parts['namespace'] = $matches[1];
			$parts['name']      = $matches[2];
			$parts['value']     = $matches[3];
		}

		return $parts;
	}
}

?>
