<?php

require_once 'NateGoSearch/NateGoSearchQuery.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/tags/PinholeAbstractMachineTag.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeSearchTag extends PinholeAbstractMachineTag
{
	// {{{ class constants

	const NAMESPACE = 'search';

	// }}}
	// {{{ private propeties

	private $name;

	private $value;

	// }}}
	// {{{ public function parse()

	public function parse($string, MDB2_Driver_Common $db)
	{
		$this->setDatabase($db);

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
			$where_clause = $this->db->quote(true, 'boolean');
			break;
		}
		
		return $where_clause;
	}

	// }}}
	// {{{ public function getJoinClauses()

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

	public function applyToPhoto(PinholePhoto $photo)
	{
		// do nothing since search tags cannot be applied to photos
	}

	// }}}
	// {{{ public function appliesToPhoto()

	public function appliesToPhoto(PinholePhoto $photo)
	{
		switch ($this->name) {
		case 'keywords':
			$applies = false;

			$sql = sprintf('select id from PinholePhoto %s
				where %s and PinholePhoto.id = %s',
				implode("\n", $this->getJoinClauses()),
				$this->getWhereClause(),
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

	protected function getNamespace()
	{
		return self::NAMESPACE;
	}

	// }}}
	// {{{ protected function getName()

	protected function getName()
	{
		return $this->name;
	}

	// }}}
	// {{{ protected function getValue()

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
