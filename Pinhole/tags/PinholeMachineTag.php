<?php

require_once 'Pinhole/tags/PinholeAbstractMachineTag.php';
require_once 'Pinhole/dataobjects/PinholeMachineTagDataObject.php';
require_once 'Swat/SwatDate.php';
require_once 'SwatDB/SwatDBTransaction.php';

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
		parent::__construct();

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
			if ($this->data_object->instance !== null)
				$this->setInstance($this->data_object->instance);
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
	 * @param SiteInstance the site instance used to parse the tag string.
	 *
	 * @return boolean true if the tag string could be parsed and false if the
	 *                  tag string could not be parsed.
	 */
	public function parse($string, MDB2_Driver_Common $db,
		SiteInstance $instance)
	{
		$this->data_object = new PinholeMachineTagDataObject();

		$this->setDatabase($db);
		$this->setInstance($instance);

		$parts = $this->getParts($string);

		if (count($parts) > 0) {
			$this->namespace = $parts['namespace'];
			$this->name      = $parts['name'];
			$this->value     = $parts['value'];
			if ($this->data_object->loadFromFields($this->namespace,
				$this->name, $this->value, $this->instance)) {
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
	// {{{ public function applyToPhoto()

	/**
	 * Applies this machine tag to a photo
	 *
	 * Any unsaved changes to the tag and photo are saved before this tag is
	 * applied to the photo.
	 *
	 * @param PinholePhoto $photo the photo this tag is to be applied to.
	 */
	public function applyToPhoto(PinholePhoto $photo)
	{
		$transaction = new SwatDBTransaction($this->db);
		try {
			// save photo and tag
			$photo->save();
			$this->save();

			// save binding
			$sql = sprintf('insert into PhotoPhotoMachineTagBinding (photo, tag)
				values (%s, %s)',
				$this->db->quote($photo->id, 'integer'),
				$this->db->quote($this->id, 'integer'));

			SwatDB::exec($this->db, $sql);

			$transaction->commit();
		} catch (Exception $e) {
			$transaction->rollback();
			throw $e;
		}

		$this->photos->add($photo);
	}

	// }}}
	// {{{ public function appliesToPhoto()

	/**
	 * Checks whether or not this machine tag applies to a given photo
	 *
	 * @param PinholePhoto the photo to check.
	 *
	 * @return boolean true if this tag applies to the given photo and false if
	 *                  this tag does not apply to the given photo. If the given
	 *                  photo does not have a database id, false is returned.
	 */
	public function appliesToPhoto(PinholePhoto $photo)
	{
		$applies = false;

		// make sure photo has an id
		if ($photo->id !== null) {
			if ($this->photos->getByIndex($photo->id) === null &&
				$this->id !== null) {
				// not in photos cache, check in database binding
				$sql = sprintf('select * from PinholePhoto
					inner join PinholePhotoMachineTagBinding on
						PinholePhoto.id =
							PinholePhotoMachineTagBinding.photo and
						PinholePhotoMachineTagBinding.tag = %s
					where id = %s',
					$this->db->quote($this->id, 'integer'),
					$this->db->quote($photo->id, 'integer'));

				$wrapper = SwatDBClassMap::get('PinholePhotoWrapper');
				$photo = SwatDB::query($this->db, $sql, $wrapper)->getFirst();

				if ($photo !== null) {
					$applies = true;
					$this->photos->add($photo);
				}
			} else {
				// in photos cache so applies
				$valid = true;
			}
		}

		return $applies;
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
	// {{{ public function setInstance()

	/**
	 * Sets the site instance used by this tag
	 *
	 * Also sets the intance for the internal tag data-object of this tag.
	 *
	 * @param SiteInstance $instance the site instance to use for this tag.
	 */
	public function setInstance(SiteInstance $instance)
	{
		parent::setInstance($instance);
		$this->data_object->setInstance($instance);
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
	public function load($id)
	{
		$loaded = false;

		if ($this->data_object->load($id)) {
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
