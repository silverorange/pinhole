<?php

/**
 * Interface for iterable tags
 *
 * Iterable tags are tags that can be incremented and decremented. For example,
 * date tags can increase and decrease their values.
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
interface PinholeIterableTag
{
	// {{{ public function next()

	/**
	 * Gets the next tag
	 *
	 * @return PinholeAbstractTag the next tag or null if there is no next tag.
	 */
	public function next();

	// }}}
	// {{{ public function prev()

	/**
	 * Gets the previous tag
	 *
	 * @return PinholeAbstractTag the previous tag or null if there is no
	 *                             previous tag.
	 */
	public function prev();

	// }}}
}

?>
