<?php

require_once 'Pinhole/tags/PinholeAbstractTag.php';
require_once 'Pinhole/dataobjects/PinholeTagDataObject.php';
require_once 'Swat/SwatDate.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBTransaction.php';

/**
 * Basic user-space tag
 *
 * This tag type is the default tag type used for simple (non-machine) tags.
 * Tags of this type are represented as an alphanumeric string and are saved
 * and loaded in the database. This tag type should be used to categorize
 * photos with a name and title. For example, photos containing red things
 * could be tagged with a tag having the name 'red' and the title 'Red'.
 * Similarly, photos on Prince Edward Island could be tagged with a tag
 * having the name 'pei' and the title 'Prince Edward Island'.
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholeTagDataObject
 */
class PinholeTag extends PinholeAbstractTag
{
	// {{{ public properties

	/**
	 * Database identifier of this tag
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Name of this tag
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Title of this tag
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Creation date of this tag
	 *
	 * @var SwatDate
	 */
	public $createdate;

	// }}}
	// {{{ private properties

	/**
	 * Encapsulated data-object used to fulfill the SwatDBRecordable interface
	 *
	 * @var PinholeTagDataObject
	 */
	private $data_object;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new tag
	 *
	 * @param PinholeTagDataObject $data_object optional. Data object to create
	 *                                           this tag from. If not
	 *                                           specified, an empty tag is
	 *                                           created.
	 */
	public function __construct(PinholeTagDataObject $data_object = null)
	{
		parent::__construct();

		if ($data_object === null) {
			$this->data_object = new PinholeTagDataObject();
			$this->createdate  = new SwatDate();
		} else {
			$this->data_object = $data_object;
			$this->id          = $this->data_object->id;
			$this->name        = $this->data_object->name;
			$this->title       = $this->data_object->title;
			$this->createdate  = $this->data_object->createdate;
		}
	}

	// }}}
	// {{{ public function parse()

	/**
	 * Parses this tag from a tag string
	 *
	 * This tag parses with any alphanumeric string.
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
		$valid = false;

		$this->data_object = new PinholeTagDataObject();

		$this->setDatabase($db);
		$this->name = $string;

		if (preg_match('/^[a-z0-9]+$/i', $string) == 1) {
			if ($this->data_object->loadFromName($this->name)) {
				$this->id         = $this->data_object->id;
				$this->title      = $this->data_object->title;
				$this->createdate = $this->data_object->createdate;
			}
			$valid = true;
		}

		return $valid;
	}

	// }}}
	// {{{ public function getTitle()

	/**
	 * Gets the title of this tag
	 *
	 * @return string the title of this tag. This returns this tag's
	 *                 <i>title</i> property.
	 */
	public function getTitle()
	{
		return $this->title;
	}

	// }}}
	// {{{ public function __toString()

	/**
	 * Gets a string representation of this tag
	 *
	 * @return string a string representation of this tag (tag string). This
	 *                  returns this tag's name property.
	 */
	public function __toString()
	{
		return $this->name;
	}

	// }}}
	// {{{ public function getWhereClause()

	/**
	 * Gets the SQL where clause for this tag
	 *
	 * @return string the SQL where clause for this tag.
	 */
	public function getWhereClause()
	{
		return sprintf('PinholePhoto.id in
			(select PinholePhotoTagBinding.photo from PinholePhotoTagBinding
			where PinholePhotoTagBinding.tag = %s)',
			$this->db->quote($this->id, 'integer'));
	}

	// }}}
	// {{{ public function applyToPhoto()

	/**
	 * Applies this tag to a photo
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
			$sql = sprintf(
				'insert into PhotoTagBinding (photo, tag) values (%s, %s)',
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

	/**
	 * Checks whether or not this tag applies to a given photo
	 *
	 * @param PinholePhoto the photo to check.
	 *
	 * @return boolean true if this tag applies to the given photo and false if
	 *                  this tag does not apply to the given photo. If the given
	 *                  photo does not have a database id or this tag does not
	 *                  have an id, false is returned.
	 */
	public function appliesToPhoto(PinholePhoto $photo)
	{
		$applies = false;

		if ($photo->id !== null && $this->id !== null) {
			$sql = sprintf('select * from PinholePhoto
				inner join PinholeTagBinding on
					PinholePhoto.id = PinholeTagBinding.id and
					PinholeTagBinding.tag = %s
				where id = %s',
				$this->db->quote($this->id, 'integer'),
				$this->db->quote($photo->id, 'integer'));

			$photo = SwatDB::query($this->db, $sql,
				'PinholePhotoWrapper')->getFirst();

			if ($photo !== null) {
				$applies = true;
				$this->photos->add($photo);
			}
		}

		return $applies;
	}

	// {{{ public function setDatabase()

	/**
	 * Sets the database connection used by this tag
	 *
	 * @param MDB2_Driver_Common $db the database connection to use for this
	 *                                tag.
	 */
	public function setDatabase(MDB2_Driver_Common $db)
	{
		parent::setDatabase($db);
		$this->data_object->setDatabase($this->db);
	}

	// }}}
	// {{{ public function save()

	/**
	 * Saves this tag to the database
	 */
	public function save()
	{
		$this->data_object->id         = $this->id;
		$this->data_object->name       = $this->name;
		$this->data_object->title      = $this->title;
		$this->data_object->createdate = $this->createdate;
		$this->data_object->save();
	}

	// }}}
	// {{{ public function load()

	/**
	 * Loads this tag from the database
	 *
	 * @param string $id the database identifier of this tag. This should be
	 *                    a numeric string.
	 *
	 * @return boolean true if this tag was loaded and false if this tag was
	 *                  not loaded.
	 */
	public function load($id)
	{
		$id = intval($id);

		$loaded = false;

		if ($this->data_object->load($id)) {
			$this->id         = $this->data_object->id;
			$this->name       = $this->data_object->name;
			$this->title      = $this->data_object->title;
			$this->createdate = $this->data_object->createdate;
			$loaded = true;
		}

		return $loaded;
	}

	// }}}
	// {{{ public function delete()

	/**
	 * Deletes this tag from the database
	 *
	 * After this tag is deleted from the database it still exists as a PHP
	 * object. 
	 */
	public function delete()
	{
		$this->data_object->delete();
	}

	// }}}
	// {{{ public function isModified()

	/**
	 * Gets whether or not this tag is modified
	 *
	 * @return boolean true if this tag has been modified and false if this
	 *                  tag has not been modified.
	 */
	public function isModified()
	{
		$this->data_object->id         = $this->id;
		$this->data_object->name       = $this->name;
		$this->data_object->title      = $this->title;
		$this->data_object->createdate = $this->createdate;
		return $this->data_object->isModified();
	}

	// }}}
}

?>
