<?php

/**
 * Machine tag for gps data
 *
 * This machine tag has the namespace 'gps' and the following names:
 *
 * - <i>latitude</i>: a gps latitude
 * - <i>longitude</i>: a gps longitude
 * - <i>box</i>: a gps bounding box
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeGpsTag extends PinholeAbstractMachineTag
{
	// {{{ class constants

	/**
	 * The namespace of the gps machine tag
	 */
	const NS = 'gps';

	// }}}
	// {{{ private propeties

	/**
	 * Name of this gps tag
	 *
	 * Should be one of 'latitude', 'longitude', or 'box'.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Value of this gps tag
	 *
	 * @var float
	 */
	private $value;

	// }}}
	// {{{ public function parse()

	/**
	 * Parses this gps tag from a tag string
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

			$this->name = $parts['name'];
			$this->value = $parts['value'];

			$valid = true;
		} else {
			$valid = false;
		}

		return $valid;
	}

	// }}}
	// {{{ public function getTitle()

	/**
	 * Gets the title of this gps tag
	 *
	 * @return string the title of this gps tag.
	 */
	public function getTitle()
	{
		switch ($this->name) {
		case 'latitude':
			$title = sprintf(Pinhole::_('Latitude: %s'),
				$this->getDisplayablePosition($this->value, true));

			break;
		case 'longitude':
			$title = sprintf(Pinhole::_('Longitude: %s'),
				$this->getDisplayablePosition($this->value, false));

			break;
		case 'box':
			$box = $this->getBox($this->value);
			$title = sprintf(Pinhole::_('Bounded By: %s × %s and %s × %s'),
				$this->getDisplayablePosition($box['max_latitude'], true),
				$this->getDisplayablePosition($box['min_longitude'], false),
				$this->getDisplayablePosition($box['min_latitude'], true),
				$this->getDisplayablePosition($box['max_longitude'], false));

			break;
		default:
			$title = Pinhole::_('Unknown GPS Data');
			break;
		}

		return $title;
	}

	// }}}
	// {{{ public function getWhereClause()

	/**
	 * Gets the SQL where clause for this gps tag
	 *
	 * @return string the SQL where clause for this gps tag.
	 */
	public function getWhereClause()
	{
		switch ($this->name) {
		case 'longitde':
		case 'latitude':
			// TODO: use round() instead
			$where = sprintf('ceil(PinholePhoto.gps_%s * 10000) = %s',
				$this->name,
				$this->db->quote(ceil($this->value * 10000), 'integer'));

			break;
		case 'box':
			$box = $this->getBox($this->value);
			$where = sprintf('PinholePhoto.gps_latitude >= %s
				and PinholePhoto.gps_latitude <= %s
				and PinholePhoto.gps_longitude >= %s
				and PinholePhoto.gps_longitude <= %s',
				$this->db->quote($box['min_latitude'], 'float'),
				$this->db->quote($box['max_latitude'], 'float'),
				$this->db->quote($box['min_longitude'], 'float'),
				$this->db->quote($box['max_longitude'], 'float'));

			break;
		default:
			$where = '';
		}

		return $where;
	}

	// }}}
	// {{{ public function applyToPhoto()

	/**
	 * Applies this tag to a photo
	 *
	 * Since gps tags cannot be applied to photos, this method does nothing.
	 *
	 * @param PinholePhoto $photo the photo this tag is to be applied to.
	 */
	public function applyToPhoto(PinholePhoto $photo)
	{
		// do nothing since gps tags cannot be applied to photos
	}

	// }}}
	// {{{ public function appliesToPhoto()

	/**
	 * Checks whether or not this search tag applies to a given photo
	 *
	 * @param PinholePhoto the photo to check.
	 *
	 * @return boolean true if this tag applies to the given photo and false if
	 *                  this tag does not apply to the given photo.
	 */
	public function appliesToPhoto(PinholePhoto $photo)
	{
		switch ($this->name) {
		case 'longitde':
		case 'latitude':
		case 'box':
			$applies = false;

			$sql = 'select * from PinholePhoto where';

			$where_clause = $this->getWhereClause();
			if ($where_clause != '')
				$sql.= $where_clause.' and ';

			$sql.= sprintf('PinholePhoto.id = %s',
				$this->db->quote($photo->id, 'integer'));

			$count = SwatDB::exec($this->db, $sql);

			$applies = ($count == 1);
			break;

		default:
			$applies = false;
			break;
		}

		return $applies;
	}

	// }}}
	// {{{ protected function getNamespace()

	/**
	 * Gets the namespace of this gps tag
	 *
	 * @return string the namespace of this gps tag.
	 */
	protected function getNamespace()
	{
		return self::NS;
	}

	// }}}
	// {{{ protected function getName()

	/**
	 * Gets the name of this gps tag
	 *
	 * @return string the name of this gps tag.
	 */
	protected function getName()
	{
		return $this->name;
	}

	// }}}
	// {{{ protected function getDisplayablePosition()

	protected function getDisplayablePosition($float, $latitude = true)
	{
		if ($latitude) {
			$hemisphere = ($float < 0) ? 'S' : 'N';
		} else {
			$hemisphere = ($float < 0) ? 'W' : 'E';
		}

		$float = abs($float);

		$degrees = floor($float);
		$minutes = floor(($float - $degrees) * 60);
		$seconds = round(($float - $degrees - ($minutes / 60)) * 3600, 1);

		return sprintf('%s° %s” %s’ %s',
			$degrees, $minutes, $seconds, $hemisphere);
	}

	// }}}
	// {{{ protected function getValue()

	/**
	 * Gets the value of this gps tag
	 *
	 * @return string the value of this gps tag.
	 */
	protected function getValue()
	{
		return $this->value;
	}

	// }}}
	// {{{ protected function getBox()

	protected function getBox($value)
	{
		$regex = '/([-+]?[0-9]*\.?[0-9]*)x([-+]?[0-9]*\.?[0-9]*)\|'.
			'([-+]?[0-9]*\.?[0-9]*)x([-+]?[0-9]*\.?[0-9]*)/';

		preg_match_all($regex, $value, $matches);

		$box = null;

		if (count($matches) == 5) {
			$box = array(
				'max_latitude'  => (float)$matches[1][0],
				'min_longitude' => (float)$matches[2][0],
				'min_latitude'  => (float)$matches[3][0],
				'max_longitude' => (float)$matches[4][0],
			);
		}

		return $box;
	}

	// }}}
	// {{{ private function isValid()

	/**
	 * Whether or not a name-value pair is valid for this gps tag
	 *
	 * @param string $name the name.
	 * @param string $value the value.
	 *
	 * @return boolean true if the name-value pair is valid for this gps tag
	 *                  and false if the name-value pair is not valid for this
	 *                  gps tag.
	 */
	private function isValid($name, $value)
	{
		switch ($name) {
		case 'latitude':
		case 'longitude':
			return is_numeric($value);
		case 'box':
			return ($this->getBox($value) !== null);
		default:
			return false;
		}
	}

	// }}}
}

?>
