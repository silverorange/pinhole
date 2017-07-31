<?php

require_once 'Pinhole/tags/PinholeAbstractMachineTag.php';
require_once 'SwatDB/SwatDBClassMap.php';

/**
 * @package   Pinhole
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeAttributeTag extends PinholeAbstractMachineTag
{
	// {{{ class constants

	/**
	 * The namespace of the meta machine tag
	 */
	const NS = 'attribute';

	// }}}
	// {{{ private propeties

	/**
	 * Value of the attribute
	 *
	 * @var boolean
	 */
	private $value;

	/**
	 * The PinholePhoto attribute
	 *
	 * @var string
	 */
	private $name;

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
		// use value property rather than the getValue() method because
		// getValue parses the string. Also use the name attribute because
		// getName() maps the db field

		return sprintf('%s.%s=%s',
			$this->getNamespace(), $this->name, $this->value);
	}

	// }}}
	// {{{ public function getTitle()

	/**
	 * Gets the title of this tag
	 *
	 * @return string the title of this tag.
	 */
	public function getTitle()
	{
		switch ($this->name) {
		case 'forsale' :
			return ($this->getValue()) ?
				Pinhole::_('For sale') : Pinhole::_('Not for sale');
		}
	}

	// }}}
	// {{{ public function getWhereClause()

	/**
	 * Gets the SQL where clause for this date tag
	 *
	 * @return string the SQL where clause for this date tag.
	 */
	public function getWhereClause()
	{
		return sprintf('PinholePhoto.%s = %s',
			$this->getName(),
			$this->getQuotedValue());
	}

	// }}}
	// {{{ public function applyToPhoto()

	/**
	 * Applies this tag to a photo
	 *
	 * @param PinholePhoto $photo the photo this tag is to be applied to.
	 */
	public function applyToPhoto(PinholePhoto $photo)
	{
		$photo->for_sale = $this->getValue();
		$photo->save();
	}

	// }}}
	// {{{ public function appliesToPhoto()

	/**
	 * Checks whether or not this tag applies to a given photo
	 *
	 * @param PinholePhoto the photo to check.
	 *
	 * @return boolean true if this tag applies to the given photo and false if
	 *                  this tag does not apply to the given photo.
	 */
	public function appliesToPhoto(PinholePhoto $photo)
	{
		return $photo->for_sale;
	}

	// }}}
	// {{{ public function parse()

	/**
	 * Parses this tag from a tag string
	 *
	 * @param string $string the tag string to parse.
	 * @param MDB2_Driver_Common $db the database connection used to parse the
	 *                            tag string.
	 * @param SiteInstance the site instance used to parse the tag string.
	 *
	 * @return boolean true if the tag string could be parsed and false if the
	 *                  tag string could not be parsed.
	 */
	public function parse(
		$string,
		MDB2_Driver_Common $db,
		SiteInstance $instance = null
	) {
		$this->setDatabase($db);
		$this->setInstance($instance);

		$parts = $this->getParts($string);
		if (count($parts) > 0 &&
			$this->isValid($parts['name'], $parts['value'])) {
			$this->value = $parts['value'];
			$this->name = $parts['name'];
			$valid = true;
		} else {
			$valid = false;
		}

		return $valid;
	}

	// }}}
	// {{{ protected function getNamespace()

	/**
	 * Gets the namespace of this tag
	 *
	 * @return string the namespace of this tag.
	 */
	protected function getNamespace()
	{
		return self::NS;
	}

	// }}}
	// {{{ protected function getName()

	/**
	 * Gets the name of this tag
	 *
	 * @return string the name of this tag.
	 */
	protected function getName()
	{
		switch ($this->name) {
		case 'forsale' :
			return 'for_sale';
		}
	}

	// }}}
	// {{{ protected function getValue()

	/**
	 * Gets the value of this tag
	 *
	 * @return boolean the value of this tag.
	 */
	protected function getValue()
	{
		switch ($this->name) {
		case 'forsale' :
			return ($this->value == 'true');
		}
	}

	// }}}
	// {{{ protected function getQuotedValue()

	/**
	 * Gets the value of this tag db-quoted
	 *
	 * @return string the quoted value of this tag.
	 */
	protected function getQuotedValue()
	{
		switch ($this->name) {
		case 'forsale' :
			return $this->db->quote($this->getValue(), 'boolean');
		}
	}

	// }}}
	// {{{ private function isValid()

	/**
	 * Whether or not a name-value pair is a valid attribute
	 *
	 * @param string $name the name.
	 * @param string $value the value.
	 *
	 * @return boolean true if the name-value pair is valid for this tag
	 *                  and false if the name-value pair is not valid for this
	 *                  tag.
	 */
	private function isValid($name, $value)
	{
		$valid = false;

		switch ($name) {
		case 'forsale' :
			if ($value == 'true' || $value == 'false')
				$valid = true;
			break;
		}

		return $valid;
	}

	// }}}
}

?>
