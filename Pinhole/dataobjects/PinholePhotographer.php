<?php

/**
 * A dataobject class for photographers
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotographer extends PinholeInstanceDataObject
{
	// {{{ constants

	/*
	 * Display the photographer in both the admin and the front-end
	 */
	const STATUS_ENABLED = 0;

	/*
	 * Display the photographer only on the front-end
	 */
	const STATUS_ARCHIVED = 1;

	/*
	 * Hide the photographer in both the admin and the front-end
	 */
	const STATUS_DISABLED = 2;

	// }}}
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Photographer's full name
	 *
	 * @var string
	 */
	public $fullname;

	/**
	 * Description
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Status
	 *
	 * Set using the PinholePhotographer::STATUS* constants
	 *
	 * @var integer
	 */
	public $status;

	// }}}
	// {{{ public static function getStatuses()

	/**
	 * Gets the array of photographer statuses
	 *
	 * @return array an array of status in the form: id => title.
	 */
	public static function getStatuses()
	{
		return array(
			self::STATUS_ENABLED  =>
				Pinhole::_('Visible on Site & Admin Photo Tools'),
			self::STATUS_ARCHIVED =>
				Pinhole::_('Visible on Site & Archived in Admin Photo Tools'),
			self::STATUS_DISABLED =>
				Pinhole::_('Not Visible on Site or Admin Photo Tools'),
		);
	}

	// }}}
	// {{{ public static function getStatusTitle()

	/**
	 * Gets the title of a photographer status
	 *
	 * @param integer $status the status to retrieve the title for.
	 *
	 * @return string the title of the specified status.
	 */
	public static function getStatusTitle($status)
	{
		$statuses = self::getStatuses();
		return $statuses[$status];
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->table = 'PinholePhotographer';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
