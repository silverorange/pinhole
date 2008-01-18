<?php

require_once 'NateGoSearch/NateGoSearchQuery.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/tags/PinholeAbstractMachineTag.php';

/**
 * Machine tag for performing searches on photos
 *
 * This machine tag has the namespace 'search' and the following names:
 *
 * - <i>keywords</i>: performs a keyword search for the tag value. If the
 *                    NateGoSearch feature is enabled a fulltext search is
 *                    performed on the keywords. Otherwise, a simple SQL
 *                    'like' search is performed.
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeSearchTag extends PinholeAbstractMachineTag
{
	// {{{ class constants

	/**
	 * The namespace of the search machine tag
	 */
	const NAMESPACE = 'search';

	// }}}
	// {{{ private propeties

	/**
	 * Name of this search tag
	 *
	 * Should be 'keywords'.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Value of this search tag
	 *
	 * @var string
	 */
	private $value;

	// }}}
	// {{{ public function parse()

	/**
	 * Parses this search tag from a tag string
	 *
	 * @param string $string the tag string to parse. 
	 * @param MDB2_Driver_Common $db the database connection used to parse the
	 *                            tag string.
	 * @param SiteInstance the site instance used to parse the tag string.
	 *
	 * @return boolean true if the tag string could be parsed and false if the
	 *                  tag string could not be parsed.
	 */
	public function parse($string, MDB2_Driver_Common $db,
		SiteInstance $instance = null)
	{
		$this->setDatabase($db);
		$this->setInstance($instance);

		$parts = $this->getParts($string);
		if (count($parts) > 0 &&
			$this->isValid($parts['name'], $parts['value'])) {

			$this->name =  $parts['name'];
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
	 * Gets the title of this search tag
	 *
	 * @return string the title of this search tag.
	 */
	public function getTitle()
	{
		switch ($this->name) {
		case 'keywords':
			$title = sprintf(Pinhole::_('Keywords: “%s”'),
				$this->getValue());

			break;

		default:
			$title = Pinhole::_('Unknown Search Tag');
			break;
		}

		return $title;
	}

	// }}}
	// {{{ public function getWhereClause()

	/**
	 * Gets the SQL where clause for this search tag
	 *
	 * If the NateGoSearch feature is enabled this returns an empty string as
	 * NateGoSearch uses table joins. See
	 * {@link PinholeSearchTag::getJoinClauses()}.
	 *
	 * @return string the SQL where clause for this search tag.
	 */
	public function getWhereClause()
	{
		switch ($this->name) {
		case 'keywords':
			// If NateGoSearch is not enabled, do a simple string match on the
			// photo title and description.
			if ($this->getPhotoSearchType() === null) {
				$where_clause = sprintf('PinholePhoto.title like %1$s or
					PinholePhoto.description like %1$s',
					$this->db->quote('%'.$this->title.'%', 'text'));
			} else {
				$where_clause = $this->db->quote(true, 'boolean');
			}

			break;

		default:
			$where_clause = '';
			break;
		}
		
		return $where_clause;
	}

	// }}}
	// {{{ public function getJoinClauses()

	/**
	 * Gets the SQL join clauses for this search tag
	 *
	 * If the NateGoSearch feature is enabled, this returns a unique join
	 * statement for the given keyword search results.
	 *
	 * @return array an array of join clauses used by this search tag.
	 */
	public function getJoinClauses()
	{
		// Ensure joined NateGoSearchResult table is unique even if we have
		// multiple keyword search tags.
		static $results_table_id = 1;

		switch ($this->name) {
		case 'keywords':
			$join_clauses = parent::getJoinClauses();

			if ($this->value !== null && $this->getPhotoSearchType() !== null) {
				$query = new NateGoSearchQuery($this->db);
				$query->addDocumentType($this->getPhotoSearchType());
				$query->addBlockedWords(
					NateGoSearchQuery::getDefaultBlockedWords());

				$result = $query->query($this->value);

				$join_clauses[] = sprintf('inner join %1$s as %4$s on
						%4$s.document_id = PinholePhoto.id and
						%4$s.unique_id = %2$s and %4$s.document_type = %3$s',
					$result->getResultTable(),
					$this->db->quote($result->getUniqueId(), 'text'),
					$this->db->quote($this->getPhotoSearchType(), 'integer'),
					$result->getResultTable().$results_table_id);
			}

			$results_table_id++;

			break;

		default:
			$join_clauses = parent::getJoinClauses();
			break;
		}

		return $join_clauses;
	}

	// }}}
	// {{{ public function applyToPhoto()

	/**
	 * Applies this tag to a photo
	 *
	 * Since search tags cannot be applied to photos, this method does nothing.
	 *
	 * @param PinholePhoto $photo the photo this tag is to be applied to.
	 */
	public function applyToPhoto(PinholePhoto $photo)
	{
		// do nothing since search tags cannot be applied to photos
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
		case 'keywords':
			$applies = false;

			$sql = 'select * from PinholePhoto';

			$join_clauses = implode(' ', $this->getJoinClauses());
			if (strlen($join_clauses) > 0)
				$sql.= ' '.$join_clauses.' ';

			$sql.= ' where ';

			$where_clause = $this->getWhereClause();
			if (strlen($where_clause) > 0)
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
	 * Gets the namespace of this search tag
	 *
	 * @return string the namespace of this search tag.
	 */
	protected function getNamespace()
	{
		return self::NAMESPACE;
	}

	// }}}
	// {{{ protected function getName()

	/**
	 * Gets the name of this search tag
	 *
	 * @return string the name of this search tag.
	 */
	protected function getName()
	{
		return $this->name;
	}

	// }}}
	// {{{ protected function getValue()

	/**
	 * Gets the value of this search tag
	 *
	 * @return string the value of this search tag.
	 */
	protected function getValue()
	{
		return $this->value;
	}

	// }}}
	// {{{ protected function getPhotoSearchType()

	protected function getPhotoSearchType()
	{
		// TODO: this should be over-ridden by the site and use a
		// constant
		return 1;
	}

	// }}}
	// {{{ private function isValid()

	/**
	 * Whether or not a name-value pair is valid for this search tag
	 *
	 * @param string $name the name.
	 * @param string $value the value.
	 *
	 * @return boolean true if the name-value pair is valid for this search tag
	 *                  and false if the name-value pair is not valid for this
	 *                  search tag.
	 */
	private function isValid($name, $value)
	{
		switch ($name) {
		case 'keywords':
			$valid = (strlen(trim($value)) > 0);
			break;

		default:
			$valid = false;
			break;
		}

		return $valid;
	}

	// }}}
}

?>
