<?php

require_once 'Swat/Swat.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/Site.php';
require_once 'Site/SiteGadgetFactory.php';
require_once 'XML/RPCAjax.php';

/**
 * Container for package wide static methods
 *
 * @package   Pinhole
 * @copyright 2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class Pinhole
{
	// {{{ constants

	/**
	 * The package identifier
	 */
	const PACKAGE_ID = 'Pinhole';

	/**
	 * The gettext domain for Pinhole
	 *
	 * This is used to support multiple locales.
	 */
	const GETTEXT_DOMAIN = 'pinhole';

	// }}}
	// {{{ public static function _()

	/**
	 * Translates a phrase
	 *
	 * This is an alias for {@link Pinhole::gettext()}.
	 *
	 * @param string $message the phrase to be translated.
	 *
	 * @return string the translated phrase.
	 */
	public static function _($message)
	{
		return Pinhole::gettext($message);
	}

	// }}}
	// {{{ public static function gettext()

	/**
	 * Translates a phrase
	 *
	 * This method relies on the php gettext extension and uses dgettext()
	 * internally.
	 *
	 * @param string $message the phrase to be translated.
	 *
	 * @return string the translated phrase.
	 */
	public static function gettext($message)
	{
		return dgettext(Pinhole::GETTEXT_DOMAIN, $message);
	}

	// }}}
	// {{{ public static function ngettext()

	/**
	 * Translates a plural phrase
	 *
	 * This method should be used when a phrase depends on a number. For
	 * example, use ngettext when translating a dynamic phrase like:
	 *
	 * - "There is 1 new item" for 1 item and
	 * - "There are 2 new items" for 2 or more items.
	 *
	 * This method relies on the php gettext extension and uses dngettext()
	 * internally.
	 *
	 * @param string $singular_message the message to use when the number the
	 *                                  phrase depends on is one.
	 * @param string $plural_message the message to use when the number the
	 *                                phrase depends on is more than one.
	 * @param integer $number the number the phrase depends on.
	 *
	 * @return string the translated phrase.
	 */
	public static function ngettext($singular_message, $plural_message, $number)
	{
		return dngettext(Pinhole::GETTEXT_DOMAIN,
			$singular_message, $plural_message, $number);
	}

	// }}}
	// {{{ public static function setupGettext()

	public static function setupGettext()
	{
		bindtextdomain(Pinhole::GETTEXT_DOMAIN, '@DATA-DIR@/Pinhole/locale');
		bind_textdomain_codeset(Pinhole::GETTEXT_DOMAIN, 'UTF-8');
	}

	// }}}
	// {{{ public static function getDependencies()

	/**
	 * Gets the packages this package depends on
	 *
	 * @return array an array of package IDs that this package depends on.
	 */
	public static function getDependencies()
	{
		return array(Swat::PACKAGE_ID, Site::PACKAGE_ID,
			XML_RPCAjax::PACKAGE_ID);
	}

	// }}}
	// {{{ public static function getConfigDefinitions()

	/**
	 * Gets configuration definitions used by the Pinhole package
	 *
	 * Applications should add these definitions to their config module before
	 * loading the application configuration.
	 *
	 * @return array the configuration definitions used by this package.
	 *
	 * @see SiteConfigModule::addDefinitions()
	 */
	public static function getConfigDefinitions()
	{
		return array(
			// Optional path prefix for all Pinhole content. If specified, this
			// must have a trailing slash. This is used to integrate Pinhole
			// content into another site.
			'pinhole.path'        => '',

			// Whether or not site is enabled
			'site.enabled' => true,

			// Whether or not search engines can index the site
			'pinhole.search_engine_indexable' => true,

			// A passphrase for viewing private photos
			'pinhole.enable_private_photos' => true,
			'pinhole.passphrase' => null,

			// An optional clustershot.com username for listing photos for sale
			'clustershot.username' => null,

			'pinhole.photos_per_page' => 50,
			'pinhole.header_image' => null,
			'pinhole.browser_index_titles' => true,

			// Comments
			// optional Wordpress API key for Akismet spam filtering.
			'pinhole.akismet_key' => null,
			// tri-state: null = set per photo,
			//            true/false = on/off for all photos
			'pinhole.global_comment_status' => null,
			'pinhole.default_comment_status' => null,

			// Save upload form data
			'pinhole.camera_time_zone' => null,
			'pinhole.set_pending' => null,
			'pinhole.set_private_photos' => null,
			'pinhole.set_content_by_meta_data' => null,
			'pinhole.set_tags_by_meta_data' => null,

			'pinhole.ad_top' => '',
			'pinhole.ad_bottom' => '',
			'pinhole.ad_referers_only' => false,
		);
	}

	// }}}
	// {{{ public static function getHtmlHeadEntrySet()

	/**
	 * Gets site-wide HTML head entries for sites using Pinhole
	 *
	 * Applications may add these head entries to their layout.
	 *
	 * @return SwatHtmlHeadEntrySet the HTML head entries used by Pinhole.
	 */
	public static function getHtmlHeadEntrySet(SiteApplication $app)
	{
		$set = new SwatHtmlHeadEntrySet();
		$pinhole_base_href = $app->config->pinhole->path;

		$set->addEntry(new SwatLinkHtmlHeadEntry($pinhole_base_href.'feed',
			'alternate','application/atom+xml', 'Feed'));

		return $set;
	}

	// }}}
	// {{{ public static function displayAd()

	/**
	 * Display an ad
	 *
	 * If $config->pinhole->ad_referers_only is true, the referer's domain is
	 * checked against the site's domain to ensure the page has been arrived at
	 * via another site.
	 *
	 * @param SiteApplication $app The current application
	 * @param string $ad_type The type of ad to display
	 */
	public static function displayAd(SiteApplication $app, $type)
	{
		$type_name = 'ad_'.$type;

		if ($app->config->pinhole->$type_name != '') {
			$base_href = $app->getBaseHref();
			$referer   = SiteApplication::initVar('HTTP_REFERER',
				null, SiteApplication::VAR_SERVER);

			// Display ad if referers only is off OR if there is a referer and
			// it does not start with the app base href.
			if (!$app->config->pinhole->ad_referers_only || ($referer !== null &&
				strncmp($referer, $base_href, strlen($base_href)) != 0)) {
				echo '<div class="ad">';
				echo $app->config->pinhole->$type_name;
				echo '</div>';
			}
		}
	}

	// }}}
}

Pinhole::setupGettext();

SwatDBClassMap::addPath(dirname(__FILE__).'/dataobjects');
SiteGadgetFactory::addPath('Pinhole/gadgets');

?>
