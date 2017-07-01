<?php

/**
 * A dataobject class for photo meta data
 *
 * @package   Pinhole
 * @copyright 2007-2013 silverorange
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
			return sprintf('ƒ/%s', (float)$value);
		case 'focallength' :
			return sprintf('%s mm', (float)$value);
		case 'exposurecompensation' :
			$fraction = self::formatAsFraction($value);
			if ($fraction === null)
				return $value;
			else
				return $fraction;
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
			$seconds = ((float)$values[0]) / ((float)$values[1]);
		else
			$seconds = (float)$value;

		$locale = SwatI18NLocale::get();

		$output = sprintf(
			Pinhole::ngettext('%s second', '%s seconds',
			($seconds == 1) ? 1 : 0),
			$locale->formatNumber(round($seconds, 6)));

		$fraction = self::formatAsFraction($value);
		if ($fraction !== null)
			$output = $fraction.' ('.$output.')';

		return $output;
	}

	// }}}
	// {{{ private static function formatAsFraction()

	private static function formatAsFraction($value)
	{
		$sign = ($value < 0) ? '-' : '';
		$value = abs($value);

		$whole_number = floor($value);
		$decimals = $value - $whole_number;

		$fraction = new Math_Fraction($decimals);
		// needs the @ because it doesn't handle references properly
		$fraction = @Math_FractionOp::simplify($fraction);

		$numerator = $fraction->getNum();
		$denominator = $fraction->getDen();

		if ($denominator > 100000) {
			// check for common denominators for values like: 0.66666667
			for ($i = 2; $i <= 600; $i++) {
				$trunc = (round($decimals * $i, 3));
				if ((int)$trunc == $trunc) {
					$numerator = $trunc;
					$denominator = $i;
					break;
				}
			}
		}

		if ($denominator != 1 && $denominator < 32000) {
			$fraction_string = $sign;

			if ($whole_number > 0)
				$fraction_string.= $whole_number.' ';

			$fraction_string.= $numerator.'/'.$denominator;
		} else {
			$fraction_string = null;
		}

		return $fraction_string;
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
