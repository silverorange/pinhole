<?php

/**
 * SabreDav WebDAV file adapter for PinholePhoto objects
 *
 * @package   Pinhole
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class PinholeDavFile implements Sabre_DAV_IFile
{
	// {{{ protected properties

	/**
	 * @var PinholePhoto
	 */
	protected $photo;

	/**
	 * @var SiteWebApplication
	 */
	protected $app;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new WebDAV adapter for a photo object
	 *
	 * @param PinholePhoto $photo the photo object.
	 */
	public function __construct(SiteWebApplication $app, PinholePhoto $photo)
	{
		$this->app   = $app;
		$this->photo = $photo;
	}

	// }}}
	// {{{ public function getName()

	/**
	 * Gets the name of this file
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->photo->original_filename;
	}

	// }}}
	// {{{ public function setName()

	/**
	 * Renames this file
	 *
	 * @param string $name the new name.
	 *
	 * @return void
	 */
	public function setName($name)
	{
		$this->photo->original_filename = basename($name);
		$this->photo->save();
	}

	// }}}
	// {{{ public function getLastModified()

	/**
	 * Gets the last modified date of this file as a UNIX timestamp
	 *
	 * @return integer the last modified date of this file as a UNIX
	 *                 timestamp.
	 */
	public function getLastModified()
	{
		$date = $this->photo->upload_date;
		return $date->getTime();
	}

	// }}}
	// {{{ public function put()

	/**
	 * Updates this file's data
	 *
	 * @param resource $data
	 *
	 * @return void
	 */
	public function put($data)
	{
		$path = sprintf('%s/%s', sys_get_temp_dir(),
			$this->photo->temp_filename);

		file_put_contents($path, $data);

		$this->photo->upload_date = new SwatDate();
		$this->photo->upload_date->toUTC();

		$this->photo->save();
	}

	// }}}
	// {{{ public function get()

	/**
	 * Gets the content of this file
	 *
	 * @return string|resource
	 */
	public function get()
	{
		$this->photo->setFileBase('../photos');
		$path = $this->photo->getFilePath('original');
		return fopen($path, 'r');
	}

	// }}}
	// {{{ public function delete()

	/**
	 * Deletes this file
	 *
	 * @return void
	 */
	public function delete()
	{
		$this->photo->setFileBase('../photos');
		$this->photo->delete();
	}

	// }}}
	// {{{ public function getSize()

	/**
	 * Gets the size of this file in bytes
	 *
	 * @return integer the size of this file in bytes.
	 */
	public function getSize()
	{
		return $this->photo->getFilesize('original');
	}

	// }}}
	// {{{ public function getETag()

	/**
	 * Gets the ETag for this file
	 *
	 * An ETag is a unique identifier representing the current version of this
	 * file.
	 *
	 * Return null if the ETag cannot effectively be determined.
	 *
	 * @return mixed a string containing the Etag of this file or null if the
	 *               ETag cannot be determined.
	 */
	public function getETag()
	{
		return md5($this->photo->original_filename.$this->getLastModified());
	}

	// }}}
	// {{{ public function getContentType()

	/**
	 * Gets the content type of this file
	 *
	 * This should be a mime-type value. If null,
	 * <kbd>application/octet-steam</kbd> is used.
	 *
	 * @return string the content type of this file.
	 */
	public function getContentType()
	{
		return $this->photo->getMimeType('original');
	}

	// }}}
}

?>
