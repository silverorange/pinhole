<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Math/Fraction.php';
require_once 'Math/FractionOp.php';

/**
 * A dataobject class for photo meta data
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhotoMetaDataBinding extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * User visible title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * A short textual identifier for this meta data
	 *
	 * This value should be read-only and is created from the embeded meta
	 * data in photos.
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * User visible value
	 *
	 * @var string
	 */
	public $value;

	/**
	 * Whether or not this meta data can be browsed as a machine tag.
	 *
	 * Allows users to browse all photos that share the same value as this
	 * meta data. Values with near-unique values like dates should not be
	 * browsed as machine tags.
	 *
	 * @var boolean
	 */
	public $machine_tag;

	// }}}
	// {{{ public function getURI()

	/**
	 * Gets the URI snippet
	 *
	 * @return string a URI tag snippet for this meta data binding.
	 */
	public function getURI()
	{
		$value = self::escapeValue($this->value);

		$uri = sprintf('meta.%s=%s',
			$this->shortname,
			$value);

		return $uri;
	}

	// }}}
	// {{{ public static function getFormattedValue()

	/**
	 * Gets a formatted value
	 *
	 * @return string The formatted value.
	 */
	public static function getFormattedValue($name, $value)
	{
		switch ($name) {
		case 'aperture' :
			return sprintf('ƒ/%s', (float) $value);
		case 'exposuretime' :
			return self::formatExposureTime($value);
		default :
			return $value;
		}
	}

	// }}}
	// {{{ private static function formatExposureTime()

	/**
	 * Gets a formatted value
	 *
	 * @return string The formatted value.
	 */
	private static function formatExposureTime($value)
	{
		$values = explode('/', $value);
		if (count($values) == 2 && $values[1] > 0)
			$seconds = ((float) $values[0]) / ((float) $values[1]);
		else
			$seconds = (float) $value;

		if ($seconds > 1) {
			$whole_number = floor($seconds);
			$decimals = $seconds - $whole_number;
		} else {
			$whole_number = 0;
			$decimals = $seconds;
		}

		$locale = SwatI18NLocale::get();

		$output = sprintf(
			Pinhole::ngettext('%s second', '%s seconds',
			($whole_number == 1 && $decimals == 0) ? 1 : 0),
			$locale->formatNumber(round($seconds, 4)));

		$fraction = new Math_Fraction($decimals);
		// needs the @ because it doesn't handle references properly
		@$fraction = Math_FractionOp::simplify($fraction);

		$numerator = $fraction->getNum();
		$denominator = $fraction->getDen();

		if ($denominator > 100000) {
			// check for common denominators for values like: 0.66666667
			for ($i = 2; $i <= 600; $i++) {
				$trunc = (round($decimals * $i, 3));
				if ((int) $trunc == $trunc) {
					$numerator = $trunc;
					$denominator = $i;
					break;
				}
			}
		}

		if ($denominator != 1)
			$output.= ' ('.$numerator.'/'.$denominator.')';

		return $output;
	}

	// }}}
	// {{{ public static function escapeValue()

	/**
	 * Escapes a meta data value for inclusion in a tag list URI
	 *
	 * @param string $string the value to be escaped.
	 *
	 * @return string the escaped value.
	 */
	public static function escapeValue($string)
	{
		$string = str_replace('|', '||', $string);
		$string = str_replace('/', '|s', $string);
		$string = rawurlencode($string);

		return $string;
	}

	// }}}
	// {{{ public static function unescapeValue()

	/**
	 * Unescapes a meta data value after inclusion in a tag list URI
	 *
	 * @param string $string the value to be unescaped.
	 *
	 * @return string the unescaped value.
	 */
	public static function unescapeValue($string)
	{
		$string = str_replace('|s', '/', $string);
		$string = str_replace('||', '|', $string);

		return $string;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->table = 'PinholePhotoMetaDataBinding';

		$this->registerInternalProperty('photo',
			SwatDBClassMap::get('PinholePhoto'));

		//$this->registerInternalProperty('metadata',
		//	$this->class_map->resolveClass('PinholeMetaData'));
	}

	// }}}
}

?>
