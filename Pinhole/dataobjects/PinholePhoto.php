<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A dataobject class for photos
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholePhoto extends SwatDBDataObject
{
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
	public $title;

	/**
	 * 
	 *
	 * @var string
	 */
	public $description;

	/**
	 * 
	 *
	 * @var Date
	 */
	public $upload_date;

	/**
	 * 
	 *
	 * @var string
	 */
	public $original_filename;

	/**
	 * 
	 *
	 * @var string
	 */
	public $exif;

	/**
	 * references PinholePhotographer(id),
	 *
	 * @var integer
	 */
	public $photographer;

	/**
	 * 
	 *
	 * @var Date
	 */
	public $photo_date;

	/**
	 * not null default 0,
	 *
	 * @var integer
	 */
	public $photo_date_parts;

	/**
	 * 
	 *
	 * @var Date
	 */
	public $publish_date;

	/**
	 * not null default false,
	 *
	 * @var boolean
	 */
	public $published;

	// }}}
	// {{{ protected function getCompressionQuality()

	protected function getCompressionQuality()
	{
		return 85;
	}

	// }}}
	// {{{ protected function getDpi()

	protected function getDpi()
	{
		return 72;
	}

	// }}}

	// processing methods
	// {{{ public function processImage()

	/**
	 * Does resizing for images
	 *
	 * @param Image_Transform $transformer the image transformer to work with.
	 *                                     The tranformer should already have
	 *                                     an image loaded.
	 *
	 * @param PinholeDimension $dimension the dimension to create.
	 *
	 * @throws SwatException if no image is loaded in the transformer.
	 */
	public function processImage(Image_Transform $transformer,
		PinholeDimension $dimension)
	{
		if ($transformer->image === null)
			throw new SwatException('No image loaded.');

		if ($dimension->max_height !== null &&
			$dimension->max_width !== null &&
			$dimension->crop_to_max)
			$this->cropToMax($transformer, $dimension);
		else
			$this->fitToMax($transformer, $dimension);

		$dpi = $this->getDpi();
		$transformer->setDpi($dpi, $dpi);

		if ($dimension->strip)
			$transformer->strip();
	}

	// }}}
	// {{{ private function fitToMax()

	private function fitToMax(Image_Transform $transformer,
		PinholeDimension $dimension)
	{
		if ($dimension->max_width !== null)
			$transformer->fitX($dimension->max_width);

		if ($dimension->max_height !== null)
			$transformer->fitY($dimension->max_height);
	}

	// }}}
	// {{{ private function cropToMax()

	private function cropToMax(Image_Transform $transformer,
		PinholeDimension $dimension)
	{
		$max_y = $dimension->max_height;
		$max_x = $dimension->max_width;

		if ($transformer->img_x / $max_x > $transformer->img_y / $max_y) {
			$new_y = $max_y;
			$new_x = ceil(($new_y / $transformer->img_y) * $transformer->img_x);
		} else {
			$new_x = $max_x;
			$new_y = ceil(($new_x / $transformer->img_x) * $transformer->img_y);
		}

		$transformer->resize($new_x, $new_y);

		// crop to fit
		if ($transformer->new_x != $max_x || $transformer->new_y != $max_y) {
			$offset_x = 0;
			$offset_y = 0;

			if ($transformer->new_x > $max_x)
				$offset_x = ceil(($transformer->new_x - $max_x) / 2);

			if ($transformer->new_y > $max_y)
				$offset_y = ceil(($transformer->new_y - $max_y) / 2);

			$transformer->crop($max_x, $max_y, $offset_x, $offset_y);
		}
	}

	// }}}
}

?>
