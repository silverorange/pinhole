<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatDateCellRenderer.php';

/**
 * A cell renderer that represents date parts as date tag links.
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeDateTagCellRenderer extends SwatDateCellRenderer
{
	// {{{ public function render()

	/**
	 * Renders the contents of this cell
	 *
	 * @see SwatCellRenderer::render()
	 */
	public function render()
	{
		if (!$this->visible)
			return;

		if ($this->date !== null) {
			// Time zone conversion mutates the original object so create a new
			// date for display. This also converts a string date to an object.
			$date = new SwatDate($this->date);
			if ($this->display_time_zone !== null) {
				if ($this->display_time_zone instanceof Date_TimeZone)
					$date->convertTZ($this->display_time_zone);
				else
					$date->convertTZbyID($this->display_time_zone);
			}

			// TODO: we might want to think about ordering these dates so they
			// match internationalized order

			$a_tag = new SwatHtmlTag('a');
			
			$a_tag->setContent($date->format('%B'));
			$a_tag->href = sprintf('tag/date.month=%s/date.year=%s',
				$date->getMonth(),
				$date->getYear());
			$a_tag->display();

			echo ' ';

			$a_tag->setContent($date->format('%e'));
			$a_tag->href = sprintf('tag/date.date=%s-%s-%s',
				$date->getYear(),
				$date->getMonth(),
				$date->getDay());
			$a_tag->display();

			echo ', ';

			$a_tag->setContent($date->format('%Y'));
			$a_tag->href = sprintf('tag/date.year=%s',
				$date->getYear());
			$a_tag->display();

			echo ' ';

			echo SwatString::minimizeEntities(
				$date->format(SwatDate::DF_TIME, $this->time_zone_format));
		}
	}

	// }}}
}

?>
