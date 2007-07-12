<?php

require_once 'Pinhole/dataobjects/PinholePhoto.php';
require_once 'SwatDB/SwatDBRecordable.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';

/**
 * Base class for tags
 *
 * Tags both select and describe photos. Tags can be combined in a tag list to
 * add greater meaning.
 *
 * As well as the abstract methods defined in this class, every tag is required
 * to have a constructor that accepts an empty parameter list. The constructor
 * may accept optional parameters but must accept an empty parameter list. This
 * is required for the tag factory to properly instantiate different tag types.
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       PinholeTagFactory::get()
 */
abstract class PinholeAbstractTag implements SwatDBRecordable
{
	// {{{ protected properties

	/**
	 * The database connection used by this tag
	 *
	 * @var MDB2_Driver_Common
	 */
	protected $db;

	/**
	 * Cache of photos this tag applies to
	 *
	 * @var PinholePhotoWrapper
	 */
	protected $photos_cache;

	// }}}
	// {{{ abstract public function parse()

	/**
	 * Parses this tag from a tag string
	 *
	 * If the tag string can be parsed, this updates this tag object with the
	 * appropriate values. Otherwise this tag object remains unaffected.
	 *
	 * @param string $string the tag string to parse.
	 * @param MDB2_Driver_Common the database connection used to parse the tag
	 *                            string.
	 *
	 * @return boolean true if the tag string could be parsed and false if the
	 *                  tag string could not be parsed.
	 */
	abstract public function parse($string, MDB2_Driver_Common $db);

	// }}}
	// {{{ abstract public function getTitle()

	/**
	 * Gets the title of this tag
	 *
	 * @return string the title of this tag.
	 */
	abstract public function getTitle();

	// }}}
	// {{{ abstract public function __toString()

	/**
	 * Gets a string representation of this tag
	 *
	 * @return string a string representation of this tag (tag string).
	 */
	abstract public function __toString();

	// }}}
	// {{{ public function getWhereClause()

	/**
	 * Gets the SQL where clause for this tag
	 *
	 * The where clause applies to the PinholePhoto table and to any tables
	 * included in the list of join clauses defined in
	 * {@link PinholeAbstractTag::getJoinClauses()}.
	 *
	 * @return string the SQL where clause for this tag.
	 *
	 * @see PinholeAbstractTag::getJoinClauses()
	 * @see PinholeAbstractTag::getRange()
	 */
	public function getWhereClause()
	{
		return 'false';
	}

	// }}}
	// {{{ public function getJoinClauses()

	/**
	 * Gets the SQL join clauses for this tag
	 *
	 * By default, photo information is only selected from the PinholePhoto
	 * table. If this tag requires information in other tables, they can be
	 * added to the array returned by this method.
	 *
	 * @return array an array of join clauses used by this tag.
	 *
	 * @see PinholeAbstractTag::getWhereClause()
	 * @see PinholeAbstractTag::getRange()
	 */
	public function getJoinClauses()
	{
		return array();
	}

	// }}}
	// {{{ public function getRange()

	/**
	 * Gets the database range for this tag
	 *
	 * If this tag affects the number of photos selected or the position in the
	 * database table photos are selected from then this method returns the
	 * required range inforamtion.
	 *
	 * @return SwatDBRange the database range for this tag or null if no range
	 *                      is defined.
	 */
	public function getRange()
	{
		return null;
	}

	// }}}

	/**
	 * Applies this tag to a photo
	 *
	 * If necessary, this method updates the database records with the correct
	 * information. Not all tag types can be applied to photos. If this tag
	 * cannot be applied to a photo, this method does nothing.
	 *
	 * @param PinholePhoto $photo the photo this tag is to be applied to.
	 *
	 * @todo this method should somehow share a cache with getPhotos().
	 */
	abstract public function applyToPhoto(PinholePhoto $photo);

	// {{{ abstract public function appliesToPhoto()

	/**
	 * Checks whether or not this tag applies to a given photo
	 *
	 * @param PinholePhoto the photo to check.
	 *
	 * @return boolean true if this tag applies to the given photo and false if
	 *                  this tag does not apply to the given photo.
	 */
	abstract public function appliesToPhoto(PinholePhoto $photo);

	// }}}

	/**
	 * Gets the photos this tag applies to
	 *
	 * @param SwatDBRange $range optional. The database range of photos to
	 *                            select.
	 *
	 * @return PinholePhotoWrapper the set of {@link PinholePhoto} objects that
	 *                              this tag applies to.
	 *
	 * @see PinholeAbstractTag::getPhotoCount()
	 *
	 * @todo this method should somehow share a cache with applyToPhoto().
	 */
	public function getPhotos(SwatDBRange $range = null)
	{
		if ($this->photos_cache === null) {
			$sql = sprintf('select * from PinholePhoto %s where %s',
				implode("\n", $this->getJoinClauses()),
				$this->getWhereClause());

			if ($range !== null)
				$this->db->setRange($range->getLimit(), $range->getOffset());

			$wrapper = SwatDBClassMap::get('PinholePhotoWrapper');
			$this->photos_cache = SwatDB::query($this->db, $sql, $wrapper);
		}

		return $this->photos_cache;
	}

	// {{{ public function getPhotoCount()

	/**
	 * Gets the number of photos this tag applies to
	 *
	 * This is more efficient than getting the set of photos and counting the
	 * set. Use this method if you don't need the actual photo objects.
	 *
	 * @return integer the number of photos this tag applies to.
	 *
	 * @see PinholeAbstractTag::getPhotos()
	 */
	public function getPhotoCount()
	{
		$sql = sprintf('select count(id) from PinholePhoto %s where %s',
			implode("\n", $this->getJoinClauses()),
			$this->getWhereClause());

		return SwatDB::queryOne($this->db, $sql);
	}

	// }}}
	// {{{ public function setDatabase()

	/**
	 * Sets the database connection used by this tag
	 *
	 * @param MDB2_Driver_Common $db the database connection to use for this
	 *                                tag.
	 */
	public function setDatabase(MDB2_Driver_Common $db)
	{
		$this->db = $db;
	}

	// }}}
	// {{{ public function save()

	/**
	 * Saves this tag to the database
	 *
	 * Not all tag types can be saved. If this tag can not be saved, this
	 * method does nothing.
	 */
	public function save()
	{
	}

	// }}}
	// {{{ public function load()

	/**
	 * Loads this tag from the database
	 *
	 * Not all tag types can be loaded. If this tag can not be loaded, this
	 * method does nothing. If this tag does support loading from the database,
	 * this method (like parse()) updates this tag with appropriate values
	 * loaded from the database.
	 *
	 * @param string $data the data used to load this tag from the database.
	 *
	 * @see PinholeAbstractTag::parse()
	 */
	public function load($data)
	{
	}

	// }}}
	// {{{ public function delete()

	/**
	 * Deletes this tag from the database
	 *
	 * After this tag is deleted from the database it still exists as a PHP
	 * object. 
	 *
	 * Not all tag types can be deleted. If this tag can not be deleted, this
	 * this method does nothing.
	 */
	public function delete()
	{
	}

	// }}}
	// {{{ public function isModified()

	/**
	 * Gets whether or not this tag is modified
	 *
	 * Not all tag types may be modified. If this tag can not be modified, this
	 * method always returns false.
	 *
	 * @return boolean true if this tag has been modified and false if this
	 *                  tag has not been modified.
	 */
	public function isModified()
	{
		return false;
	}

	// }}}
}

?>
