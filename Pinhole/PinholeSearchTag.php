<?php

require_once 'Pinhole/PinholeMachineTag.php';
require_once 'NateGoSearch/NateGoSearchQuery.php';

/**
 * @package   Pinhole
 * @copyright 2007 silverorange
 */
class PinholeSearchTag extends PinholeMachineTag
{
	// {{{ protected properties

	protected $name_space = 'search';

	// }}}
	// {{{ public function isValid()

	public function isValid()
	{
		return (strlen(trim($this->value)) > 0);
	}

	// }}}
	// {{{ public function getTitle()

	public function getTitle()
	{
		return sprintf(Pinhole::_('Keywords: “%s”'),
			$this->value);
	}

	// }}}
	// {{{ public function getWhereClause()

	public function getWhereClause()
	{
		// keywords are included in the where clause if fulltext searching
		// is turned off
		if ($this->getPhotoSearchType() === null)
			return sprintf('(PinholePhoto.title like %1$s
				or PinholePhoto.description like %1$s)',
				$this->db->quote('%'.$this->title.'%', 'text'));
		else
			return ' 1 = 1';
	}

	// }}}
	// {{{ public function getJoinClause()

	public function getJoinClause()
	{
		static $count = 0;

		$count++;

		if (strlen(trim($this->value)) > 0 &&
			$this->getPhotoSearchType() !== null) {

			$query = new NateGoSearchQuery($this->db);
			$query->addDocumentType($this->getPhotoSearchType());
			$query->addBlockedWords(
				NateGoSearchQuery::getDefaultBlockedWords());

			$result = $query->query($this->value);

			$join_clause = sprintf(
				'inner join %1$s as %4$s on
					%4$s.document_id = PinholePhoto.id and
					%4$s.unique_id = %2$s and %4$s.document_type = %3$s',
				$result->getResultTable(),
				$this->db->quote($result->getUniqueId(), 'text'),
				$this->db->quote($this->getPhotoSearchType(),
					'integer'),
				$result->getResultTable().$count);

			// TODO: do we need to somehow order the results by
			// rank?
			/*
			$this->order_by_clause =
				sprintf('%1$s.displayorder1, %1$s.displayorder2, PinholePhoto.title',
					$result->getResultTable());
			*/
		} else {
			$join_clause = '';
			//$this->order_by_clause = 'PinholePhoto.title';
		}

		return $join_clause;
	}

	// }}}
	// {{{ protected function getPhotoSearchType()

	/**
	 * Gets the search type for photos for this web-application
	 *
	 * @return integer the search type for photos for this web-application or
	 *                  null if fulltext searching is not implemented for the
	 *                  current application.
	 */
	protected function getPhotoSearchType()
	{
		// TODO: this should be over-ridden by the site and use a
		// constant
		return 1;
	}

	// }}}
}

?>
