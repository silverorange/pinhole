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
	public $id;
	public $namespace;
	public $name;
	public $value;
	public $createdate;

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

	public function __toString()
	{
		return sprintf('%s.%s=%s',
			$this->namespace, $this->name, $this->value);
	}

	public function getTitle()
	{
		return sprintf('%s: %s = %s',
			$this->namespace, $this->name, $this->value);
	}

	public function getWhereClause()
	{
		return sprintf('PinholePhoto.id in
			(select PinholePhotoMachineTagBinding.photo
				from PinholePhotoMachineTagBinding
			where PinholePhotoMachineTagBinding.tag = %s)',
			$this->db->quote($this->id, 'integer'));
	}

	public function save()
	{
		$this->data_object->id = $this->id;
		$this->data_object->namespace = $this->namespace;
		$this->data_object->name = $this->name;
		$this->data_object->value = $this->value;
		$this->data_object->createdate = $this->createdate;
		$this->data_object->save();
	}

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

	public function delete()
	{
		$this->data_object->delete();
	}

	public function isModified()
	{
		$this->data_object->id         = $this->id;
		$this->data_object->namespace  = $this->namespace;
		$this->data_object->name       = $this->name;
		$this->data_object->value      = $this->value;
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

	protected function getNamespace()
	{
		return $this->namespace;
	}

	protected function getName()
	{
		return $this->name;
	}

	protected function getValue()
	{
		return $this->value;
	}
}

?>
