<?php

require_once 'Pinhole/dataobjects/PinholeMetaData.php';
require_once 'Pinhole/dataobjects/PinholePhotoMetaDataBinding.php';
require_once 'Pinhole/tags/PinholeAbstractMachineTag.php';
require_once 'SwatDB/SwatDBClassMap.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeMetaTag extends PinholeAbstractMachineTag
{
	// {{{ class constants

	/**
	 * The namespace of the meta machine tag
	 */
	const NAMESPACE = 'meta';

	// }}}
	// {{{ private propeties

	/**
	 * Value of this meta tag
	 *
	 * @var string
	 */
	private $value;

	/**
	 * The meta-data object of this meta tag
	 *
	 * @var PinholeMetaData
	 */
	private $meta_data;

	// }}}
	// {{{ public function parse()

	/**
	 * Parses this meta tag from a tag string
	 *
	 * @param string $string the tag string to parse. 
	 * @param MDB2_Driver_Common $db the database connection used to parse the
	 *                            tag string.
	 *
	 * @return boolean true if the tag string could be parsed and false if the
	 *                  tag string could not be parsed.
	 */
	public function parse($string, MDB2_Driver_Common $db)
	{
		$this->setDatabase($db);

		$parts = $this->getParts($string);
		if (count($parts) > 0 &&
			$this->isValid($parts['name'], $parts['value'])) {
			$this->value =
				PinholePhotoMetaDataBinding::unescapeValue($parts['value']);

			$valid = true;
		} else {
			$valid = false;
		}

		return $valid;
	}

	// }}}
	// {{{ public function getTitle()

	/**
	 * Gets the title of this meta tag
	 *
	 * @return string the title of this meta tag.
	 */
	public function getTitle()
	{
		return sprintf(Pinhole::_('%s: %s'),
			$this->meta_data->title, $this->value);
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
		return sprintf('PinholePhoto.id in
			(select PinholePhotoMetaDataBinding.photo
				from PinholePhotoMetaDataBinding
			where PinholePhotoMetaDataBinding.meta_data = %s and
				lower(PinholePhotoMetaDataBinding.value) = lower(%s))',
			$this->db->quote($this->meta_data->id, 'integer'),
			$this->db->quote($this->value, 'text'));
	}

	// }}}
	// {{{ public function applyToPhoto()

	/**
	 * Applies this tag to a photo
	 *
	 * This creates a binding between the PinholeMetaData object and the
	 * PinholePhoto. Any unsaved changes to the photo are saved before this
	 * tag is applied to the photo.
	 *
	 * @param PinholePhoto $photo the photo this tag is to be applied to.
	 */
	public function applyToPhoto(PinholePhoto $photo)
	{
		$transaction = new SwatDBTransaction($this->db);
		try {
			// save photo
			$photo->save();

			// save binding
			$sql = sprintf(
				'insert into PhotoPhotoMetaDataBinding (photo, meta_data, value)
				values (%s, %s, %s)',
				$this->db->quote($photo->id, 'integer'),
				$this->db->quote($this->meta_data->id, 'integer'),
				$this->db->quote($this->value, 'text'));

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
	 * Checks whether or not this meta tag applies to a given photo
	 *
	 * @param PinholePhoto the photo to check.
	 *
	 * @return boolean true if this tag applies to the given photo and false if
	 *                  this tag does not apply to the given photo.
	 */
	public function appliesToPhoto(PinholePhoto $photo)
	{
		$applies = false;

		// make sure photo has an id
		if ($photo->id !== null) {
			if ($this->photos->getByIndex($photo->id) === null) {
				// not in photos cache, check in database binding
				$sql = sprintf('select * from PinholePhoto
					inner join PinholePhotoMetaDataBinding on
						PinholePhoto.id =
							PinholePhotoMetaDataBinding.photo and
						PinholePhotoMetaDataBinding.meta_data = %s and
						lower(PinholePhotoMetaDataBinding.value) = lower(%s)
					where id = %s',
					$this->db->quote($this->meta_data->id, 'integer'),
					$this->db->quote($this->value, 'text'),
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
	// {{{ protected function getNamespace()

	/**
	 * Gets the namespace of this meta tag
	 *
	 * @return string the namespace of this meta tag.
	 */
	protected function getNamespace()
	{
		return self::NAMESPACE;
	}

	// }}}
	// {{{ protected function getName()

	/**
	 * Gets the name of this meta tag
	 *
	 * @return string the name of this meta tag.
	 */
	protected function getName()
	{
		return $this->meta_data->shortname;
	}

	// }}}
	// {{{ protected function getValue()

	/**
	 * Gets the value of this meta tag
	 *
	 * @return string the value of this meta tag.
	 */
	protected function getValue()
	{
		return $this->value;
	}

	// }}}
	// {{{ private function isValid()

	/**
	 * Whether or not a name-value pair is valid for this meta tag
	 *
	 * @param string $name the name.
	 * @param string $value the value.
	 *
	 * @return boolean true if the name-value pair is valid for this meta tag
	 *                  and false if the name-value pair is not valid for this
	 *                  meta tag.
	 */
	private function isValid($name, $value)
	{
		$valid = false;

		$class_name = SwatDBClassMap::get('PinholeMetaData');
		$this->meta_data = new $class_name();
		$this->meta_data->setDatabase($this->db);

		// ensure meta data object exists
		$valid = $this->meta_data->loadFromShortname($name);

		return $valid;
	}

	// }}}
}

?>
