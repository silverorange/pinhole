<?php

require_once 'Pinhole/tags/PinholeAbstractTag.php';
require_once 'Pinhole/dataobjects/PinholeTagDataObject.php';
require_once 'Swat/SwatDate.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Tag
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeTag extends PinholeAbstractTag
{
	public $id;

	public $name;

	public $title;

	/**
	 * @var SwatDate
	 */
	public $createdate;

	/**
	 * @var PinholeTagDataObject
	 */
	private $data_object;

	public function __construct(PinholeTagDataObject $data_object = null)
	{
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

	public function parse($string, MDB2_Driver_Common $db)
	{
		$this->data_object = new PinholeTagDataObject();

		$this->setDatabase($db);
		$this->name = $string;

		if ($this->data_object->loadFromName($this->name)) {
			$this->id         = $this->data_object->id;
			$this->title      = $this->data_object->title;
			$this->createdate = $this->data_object->createdate;
		}

		return true;
	}

	public function getTitle()
	{
		return $this->title;
	}

	public function __toString()
	{
		return $this->name;
	}

	public function getWhereClause()
	{
		return sprintf('PinholePhoto.id in
			(select PinholePhotoTagBinding.photo from PinholePhotoTagBinding
			where PinholePhotoTagBinding.tag = %s)',
			$this->db->quote($this->id, 'integer'));
	}

	public function save()
	{
		$this->data_object->id         = $this->id;
		$this->data_object->name       = $this->name;
		$this->data_object->title      = $this->title;
		$this->data_object->createdate = $this->createdate;
		$this->data_object->save();
	}

	public function load($id)
	{
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

	public function delete()
	{
		$this->data_object->delete();
	}

	public function isModified()
	{
		$this->data_object->id         = $this->id;
		$this->data_object->name       = $this->name;
		$this->data_object->title      = $this->title;
		$this->data_object->createdate = $this->createdate;
		return $this->data_object->isModified();
	}

	public function setDatabase(MDB2_Driver_Common $db)
	{
		parent::setDatabase($db);
		$this->data_object->setDatabase($this->db);
	}

	public function applyToPhoto(PinholePhoto $photo)
	{
		// TODO
		// 1. save this tag
		// 2. update or insert photo tag binding
		// 3. add photo to 'applies' cache
	}

	public function appliesToPhoto(PinholePhoto $photo)
	{
		// TODO
		// 1. check if photo tag binding exists
		// 2. add photo to 'applies' cache if binding exists
		// 3. return value
	}
}

?>
