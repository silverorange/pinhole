<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A dataobject class for photographers
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotographer extends SwatDBDataObject
{
	// {{{ constants

	const STATUS_ENABLED = 0;
	const STATUS_ARCHIVED = 1;
	const STATUS_DISABLED = 2;

	// }}}
	// {{{ private properties

	private $statuses = array();

	// }}}
	// {{{ public properties

	/**
	 * 
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * 
	 *
	 * @var string
	 */
	public $fullname;

	/**
	 * 
	 *
	 * @var string
	 */
	public $description;

	/**
	 * status
	 *
	 * @var integer
	 */
	public $status;

	// }}}
	// {{{ public static function getStatuses()

	public function getStatuses()
	{
		return array(
			self::STATUS_ENABLED  => Pinhole::_('Visible on Site & Admin Photo Tools'),
			self::STATUS_ARCHIVED => Pinhole::_('Visible on Site & Archived in Admin Photo Tools'),
			self::STATUS_DISABLED => Pinhole::_('Not Visible on Site or Admin Photo Tools'),
		);
	}

	// }}}
	// {{{ public static function getStatusTitle()

	public static function getStatusTitle($status)
	{
		$statuses = self::getStatuses();
		return $statuses[$status];
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'PinholePhotographer';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
