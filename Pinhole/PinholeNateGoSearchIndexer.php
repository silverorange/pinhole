<?php

require_once 'Swat/SwatDetailsStore.php';
require_once 'Site/SiteNateGoSearchIndexer.php';
require_once 'Pinhole/Pinhole.php';
require_once 'Pinhole/dataobjects/PinholePhotoWrapper.php';

/**
 * Pinhole search indexer application for NateGoSearch
 *
 * This indexer indexed photos, tags and articles by default.
 * Subclasses may change how and what gets indexed.
 *
 * @package   Pinhole
 * @copyright 2006-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeNateGoSearchIndexer extends SiteNateGoSearchIndexer
{
	// {{{ public function queue()

	/**
	 * Repopulates the entire search queue
	 */
	public function queue()
	{
		parent::queue();

		$this->queueTags();
		$this->queuePhotos();
	}

	// }}}
	// {{{ protected function index()

	/**
	 * Indexes documents
	 *
	 * Subclasses should override this method to add or remove additional
	 * indexed tables.
	 */
	protected function index()
	{
		parent::index();

		$this->indexTags();
		$this->indexPhotos();
	}

	// }}}
	// {{{ protected function queueTags()

	/**
	 * Repopulates the tags queue
	 */
	protected function queueTags()
	{
		$this->output(Pinhole::_('Repopulating tag search queue ... '),
			self::VERBOSITY_ALL);

		$type = NateGoSearch::getDocumentType($this->db, 'tag');

		// clear queue 
		$sql = sprintf('delete from NateGoSearchQueue
			where document_type = %s',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);

		// fill queue
		$sql = sprintf('insert into NateGoSearchQueue
			(document_type, document_id) select %s, id from PinholeTag',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);

		$this->output(Pinhole::_('done')."\n", self::VERBOSITY_ALL);
	}

	// }}}
	// {{{ protected function queuePhotos()

	/**
	 * Repopulates the photos queue
	 */
	protected function queuePhotos()
	{
		$this->output(Pinhole::_('Repopulating photo search queue ... '),
			self::VERBOSITY_ALL);

		$type = NateGoSearch::getDocumentType($this->db, 'photo');

		// clear queue 
		$sql = sprintf('delete from NateGoSearchQueue
			where document_type = %s',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);

		// fill queue 
		$sql = sprintf('insert into NateGoSearchQueue
			(document_type, document_id) select %s, id from PinholePhoto',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);

		$this->output(Pinhole::_('done')."\n", self::VERBOSITY_ALL);
	}

	// }}}
	// {{{ protected function indexTags()

	/**
	 * Indexes tags
	 */
	protected function indexTags()
	{
		$spell_checker = new NateGoSearchPSpellSpellChecker('en');
		$spell_checker->setCustomWordList($this->getCustomWordList());
		$spell_checker->loadCustomContent();

		$indexer = new NateGoSearchIndexer('tag', $this->db);

		$indexer->addTerm(new NateGoSearchTerm('name'));
		$indexer->addTerm(new NateGoSearchTerm('title'));
		$indexer->setMaximumWordLength(32);
		$indexer->addUnindexedWords(
			NateGoSearchIndexer::getDefaultUnindexedWords());

		$type = NateGoSearch::getDocumentType($this->db, 'tag');

		$sql = sprintf('select PinholeTag.id, PinholeTag.title,
				PinholeTag.name
			from PinholeTag
			inner join NateGoSearchQueue
				on PinholeTag.id = NateGoSearchQueue.document_id
				and NateGoSearchQueue.document_type = %s',
			$this->db->quote($type, 'integer'));

		$this->output(Pinhole::_('Indexing tags ... ').'   ',
			self::VERBOSITY_ALL);

		$tags = SwatDB::query($this->db, $sql);
		$total = count($tags);
		$count = 0;
		foreach ($tags as $tag) {

			if ($count % 10 == 0) {
				$indexer->commit();
				$this->output(str_repeat(chr(8), 3), self::VERBOSITY_ALL);
				$this->output(sprintf('%2d%%', ($count / $total) * 100),
					self::VERBOSITY_ALL);
			}

			$document = new NateGoSearchDocument($tag, 'id');
			$indexer->index($document);

			$count++;
		}

		$this->output(str_repeat(chr(8), 3).Pinhole::_('done')."\n",
			self::VERBOSITY_ALL);

		$indexer->commit();
		unset($indexer);

		$sql = sprintf('delete from NateGoSearchQueue where document_type = %s',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);
	}

	// }}}
	// {{{ protected function indexPhotos()

	protected function indexPhotos()
	{
		$spell_checker = new NateGoSearchPSpellSpellChecker('en');
		$spell_checker->setCustomWordList($this->getCustomWordList());
		$spell_checker->loadCustomContent();

		$photo_indexer = new NateGoSearchIndexer('photo', $this->db);
		$photo_indexer->setSpellChecker($spell_checker);

		$photo_indexer->addTerm(new NateGoSearchTerm('title', 5));
		$photo_indexer->addTerm(new NateGoSearchTerm('tags', 2));
		$photo_indexer->addTerm(new NateGoSearchTerm('description'));
		$photo_indexer->setMaximumWordLength(32);
		$photo_indexer->addUnindexedWords(
			NateGoSearchIndexer::getDefaultUnindexedWords());

		$type = NateGoSearch::getDocumentType($this->db, 'photo');

		$sql = sprintf('select PinholePhoto.*
			from PinholePhoto
				left outer join PinholePhotoTagBinding on
					PinholePhotoTagBinding.photo = PinholePhoto.id
				left outer join PinholeTag on
					PinholeTag.id = PinholePhotoTagBinding.tag
				inner join NateGoSearchQueue
					on PinholePhoto.id = NateGoSearchQueue.document_id
					and NateGoSearchQueue.document_type = %s
			order by PinholePhoto.id',
			$this->db->quote($type, 'integer'));

		$this->output(Pinhole::_('Indexing photos ... ').'   ',
			self::VERBOSITY_ALL);

		$photos = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('PinholePhotoWrapper'));

		$total = count($photos);
		$count = 0;
		$current_photo_id = null;
		foreach ($photos as $photo) {
			$ds = new SwatDetailsStore($photo);
			$ds->title = $photo->getTitle();

			$tags = '';
			foreach ($photo->tags as $tag)
				$tags.= ' '.$tag->title.' '.$tag->name;

			$ds->tags = $tags;

			if ($count % 10 == 0) {
				$photo_indexer->commit();
				$this->output(str_repeat(chr(8), 3), self::VERBOSITY_ALL);
				$this->output(sprintf('%2d%%', ($count / $total) * 100),
					self::VERBOSITY_ALL);
			}

			$document = new NateGoSearchDocument($ds, 'id');

			$photo_indexer->index($document);
			$current_photo_id = $photo->id;
			$count++;
		}

		$this->output(str_repeat(chr(8), 3).Pinhole::_('done')."\n",
			self::VERBOSITY_ALL);

		define('SWATDB_DEBUG', true);
		$photo_indexer->commit();
		exit;
		$tag_indexer->commit();
		unset($photo_indexer);
		unset($tag_indexer);

		$sql = sprintf('delete from NateGoSearchQueue where document_type = %s',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);
	}

	// }}}
}

?>
