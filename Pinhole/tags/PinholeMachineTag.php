<?php

require_once 'Pinhole/tags/PinholeAbstractTag.php';
require_once 'Pinhole/dataobjects/PinholeMachineTagDataObject.php';

/**
 * Generic machine tag
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeMachineTag extends PinholeAbstractMachineTag
{
	// {{{ public properties

	/**
	 * Database identifier of this machine tag
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Namespace of this machine tag
	 *
	 * @var string
	 */
	public $namespace;

	/**
	 * Name of this machine tag
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Value of this machine tag
	 *
	 * @var string
	 */
	public $value;

	/**
	 * Creation date of this machine tag
	 *
	 * @var SwatDate
	 */
	public $createdate;

	// }}}
	// {{{ private properties

	/**
	 * Encapsulated data-object used to fulfill the SwatDBRecordable interface
	 *
	 * @var PinholeMachineTagDataObject
	 */
	private $data_object;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new machine tag
	 *
	 * @param PinholeMachineTagDataObject $data_object optional. Data object to
	 *                                                  create this tag from. If
	 *                                                  not specified, an empty
	 *                                                  tag is created.
	 */
	public function __construct(PinholeMachineTagDataObject $data_object = null)
	{
		if ($data_object === null) {
			$this->data_object = new PinholeMachineTagDataObject();
			$this->createdate  = new SwatDate();
		} else {
			$this->data_object = $data_object;
			$this->id          = $this->data_object->id;
			$this->namespace   = $this->data_object->namespace;
			$this->name        = $this->data_object->name;
			$this->value       = $this->data_object->value;
			$this->createdate  = $this->data_object->createdate;
		}
	}

	// }}}
	// {{{ public function parse()

	/**
	 * Parses this tag from a tag string
	 *
	 * The tag string must match the machine tag syntax defined in
	 * {@link PinholeAbstractMachineTag::SYNTAX_PATTERN}.
	 *
	 * @param string $string the tag string to parse. 
	 * @param MDB2_Driver_Common the database connection used to parse the tag
	 *                            string.
	 *
	 * @return boolean true if the tag string could be parsed and false if the
	 *                  tag string could not be parsed.
	 */
	public function parse($string, MDB2_Driver_Common $db)
	{
		$this->data_object = new PinholeMachineTagDataObject();

		$this->setDatabase($db);
		$parts = $this->getParts($string);

		if (count($parts) > 0) {
			$this->namespace = $parts['namespace'];
			$this->name      = $parts['name'];
			$this->value     = $parts['value'];
			if ($this->data_object->loadFromFields($this->namespace,
				$this->name, $this->value)) {
				$this->id         = $this->data_object->id;
				$this->createdate = $this->data_object->createdate;
			}
			$valid = true;
		} else {
			$valid = false;
		}

		return $valid;
	}

	// }}}
	// {{{ public function getTitle()

	/**
	 * Gets the title of this tag
	 *
	 * @return string the title of this tag. Machine tag titles are
	 *                 'namespace: name = value'.
	 */
	public function getTitle()
	{
		return sprintf('%s: %s = %s',
			$this->namespace, $this->name, $this->value);
	}

	// }}}
	// {{{ public function getWhereClause()

	/**
	 * Gets the SQL where clause for this machine tag
	 *
	 * @return string the SQL where clause for this machine tag.
	 */
	public function getWhereClause()
	{
		return sprintf('PinholePhoto.id in
			(select PinholePhotoMachineTagBinding.photo
				from PinholePhotoMachineTagBinding
			where PinholePhotoMachineTagBinding.tag = %s)',
			$this->db->quote($this->id, 'integer'));
	}

	// }}}
	// {{{ public function save()

	/**
	 * Saves this machine tag to the database
	 */
	public function save()
	{
		$this->data_object->id = $this->id;
		$this->data_object->namespace = $this->namespace;
		$this->data_object->name = $this->name;
		$this->data_object->value = $this->value;
		$this->data_object->createdate = $this->createdate;
		$this->data_object->save();
	}

	// }}}
	// {{{ public function load()

	/**
	 * Loads this machine tag from the database
	 *
	 * @param string $id the database identifier of this machine tag. This
	 *                    should be a numeric string.
	 *
	 * @return boolean true if this tag was loaded and false if this tag was
	 *                  not loaded.
	 */
	public function load($name)
	{
		$loaded = false;

		if ($this->data_object->load($name)) {
			$this->id         = $this->data_object->id;
			$this->namespace  = $this->data_object->namespace;
			$this->name       = $this->data_object->name;
			$this->value      = $this->data_object->value;
			$this->createdate = $this->data_object->createdate;
			$loaded = true;
		}

		return $loaded;
	}

	// }}}
	// {{{ public function delete()

	/**
	 * Deletes this machine tag from the database
	 *
	 * After this machine tag is deleted from the database it still exists as a
	 * PHP object. 
	 */
	public function delete()
	{
		$this->data_object->delete();
	}

	// }}}
	// {{{ public function isModified()

	/**
	 * Gets whether or not this machine tag is modified
	 *
	 * @return boolean true if this tag has been modified and false if this
	 *                  tag has not been modified.
	 */
	public function isModified()
	{
		$this->data_object->id         = $this->id;
		$this->data_object->namespace  = $this->namespace;
		$this->data_object->name       = $this->name;
		$this->data_object->value      = $this->value;
		$this->data_object->createdate = $this->createdate;
		return $this->data_object->isModified();
	}

	// }}}
	// {{{ public function setDatabase()

	/**
	 * Sets the database connection used by this machine tag
	 *
	 * @param MDB2_Driver_Common $db the database connection to use for this
	 *                                machine tag.
	 */
	public function setDatabase(MDB2_Driver_Common $db)
	{
		parent::setDatabase($db);
		$this->data_object->setDatabase($this->db);
	}

	// }}}

	/**
	 * Applies this machine tag to a photo
	 *
	 * @param PinholePhoto $photo the photo this tag is to be applied to.
	 *
	 * @todo implement this method.
	 */
	public function applyToPhoto(PinholePhoto $photo)
	{
		// TODO
		// 1. save this tag
		// 2. update or insert photo tag binding
		// 3. add photo to 'applies' cache
	}

	/**
	 * Checks whether or not this machine tag applies to a given photo
	 *
	 * @param PinholePhoto the photo to check.
	 *
	 * @return boolean true if this tag applies to the given photo and false if
	 *                  this tag does not apply to the given photo.
	 *
	 * @todo implement this method.
	 */
	public function appliesToPhoto(PinholePhoto $photo)
	{
		// TODO
		// 1. check if photo tag binding exists
		// 2. add photo to 'applies' cache if binding exists
		// 3. return value
	}

	// {{{ protected function getNamespace()

	/**
	 * Gets the namespace of this machine tag
	 *
	 * @return string the namespace of this machine tag. This returns this tag's
	 *                 <i>$namespace</i> property.
	 */
	protected function getNamespace()
	{
		return $this->namespace;
	}

	// }}}
	// {{{ protected function getName()

	/**
	 * Gets the name of this machine tag
	 *
	 * @return string the name of this machine tag. This returns this tag's
	 *                 <i>$name</i> property.
	 */
	protected function getName()
	{
		return $this->name;
	}

	// }}}
	// {{{ protected function getValue()

	/**
	 * Gets the value of this machine tag
	 *
	 * @return string the value of this machine tag. This returns this tag's
	 *                 <i>$value</i> property.
	 */
	protected function getValue()
	{
		return $this->value;
	}

	// }}}
}

?>
