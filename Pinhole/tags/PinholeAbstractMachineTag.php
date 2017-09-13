<?php

/**
 * Base class for machine tags
 *
 * Machine tags are special tags that follow certain conventions. All machine
 * tags consist of a namespace, name and value. The namespace roughly
 * corresponds to a tag type and the name and value determine the specific
 * properties of the tag.
 *
 * A machine tag string looks like 'namespace.name=value'.
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class PinholeAbstractMachineTag extends PinholeAbstractTag
{
	// {{{ class constants

	/**
	 * A regular expression for the syntax pattern used by all machine tags.
	 *
	 * The specific parts of the machine tag are captured in the regular
	 * expression. [1] is the namespace, [2] is the name and [3] is the value.
	 */
	const SYNTAX_PATTERN = '/^([a-z]+)\.([a-z]+)=(.*)$/';

	// }}}
	// {{{ public function __toString()

	/**
	 * Gets a string representation of this machine tag
	 *
	 * The string representation is typically 'namespace.name=value'.
	 *
	 * @return string a string representation (tag string) of this machine tag.
	 */
	public function __toString()
	{
		return sprintf('%s.%s=%s',
			$this->getNamespace(), $this->getName(), $this->getValue());
	}

	// }}}
	// {{{ abstract protected function getNamespace()

	/**
	 * Gets the namespace of this machine tag
	 *
	 * @return string the namespace of this machine tag.
	 */
	abstract protected function getNamespace();

	// }}}
	// {{{ abstract protected function getName()

	/**
	 * Gets the name of this machine tag
	 *
	 * @return string the name of this machine tag.
	 */
	abstract protected function getName();

	// }}}
	// {{{ abstract protected function getValue()

	/**
	 * Gets the value of this machine tag
	 *
	 * @return string the value of this machine tag.
	 */
	abstract protected function getValue();

	// }}}
	// {{{ protected final function getParts()

	/**
	 * Gets machine tag parts from a string
	 *
	 * If the string cannot be parsed into machine tag parts, an empty array
	 * is returned.
	 *
	 * @param string $string the string from which to get machine tag parts.
	 *
	 * @return array an array with the keys 'namespace', 'name' and 'value'
	 *                pointing to the appropriate parts of the string. If the
	 *                string could not be parsed into parts, the returned
	 *                array is empty.
	 */
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

	// }}}
}

?>
