<?php
/**
 * Joomla.org site template
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Helper class for the Joomla template
 */
class JoomlaTemplateHelper
{
	/**
	 * Retrieve the Site Configuration for Google Tag Manager, issue reporting links, script IDs and cookie categories
	 *
	 * Note that this helper method is only 'good' for live sites, for development environments no data is returned
	 *
	 * @param   string  $siteUrl  The site URL without the scheme
	 *
	 * @return  stdClass The site configuration data
	 */
	public static function getSiteConfig($siteUrl)
	{
		switch ($siteUrl)
		{
			case 'api.joomla.org':
			{
				$issueTag   = 'japi';
				$siteConfig = (object) [
					'gtmId'   => 'GTM-NDWJB8',
					'scripts' => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => '59300ad15992c776ad970068'
					],
					'cookies' => (object) [
						'performance' => 'true',
						'functional'  => 'false',
						'advertising' => 'false'
					],
				];

				break;
			}

			case 'certification.joomla.org':
			{
				$issueTag   = 'jcertif';
				$siteConfig = (object) [
					'gtmId'   => 'GTM-PFP9MJ',
					'scripts' => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => '59300ad15992c776ad970068'
					],
					'cookies' => (object) [
						'performance' => 'true',
						'functional'  => 'false',
						'advertising' => 'false'
					]
				];

				break;
			}

			case 'community.joomla.org':
			{
				$issueTag   = 'jcomm';
				$siteConfig = (object) [
					'gtmId'   => 'GTM-WQNG7Z',
					'scripts' => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => '59300ad15992c776ad970068'
					],
					'cookies' => (object) [
						'performance' => 'true',
						'functional'  => 'true',
						'advertising' => 'true'
					]
				];

				break;
			}

			case 'conference.joomla.org':
			{
				$issueTag   = 'jconf';
				$siteConfig = (object) [
					'gtmId'   => 'GTM-PZWNZR',
					'scripts' => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'addthis'   => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => 'undefined'
					],
					'cookies' => (object) [
						'performance' => 'true',
						'functional'  => 'true',
						'advertising' => 'true'
					]
				];

				break;
			}

			case 'developer.joomla.org':
			{
				$issueTag   = 'jdev';
				$siteConfig = (object) [
					'gtmId'   => 'GTM-WJ36D4',
					'scripts' => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => '59300ad15992c776ad970068'
					],
					'cookies' => (object) [
						'performance' => 'true',
						'functional'  => 'false',
						'advertising' => 'false'
					]
				];

				break;
			}

			case 'downloads.joomla.org':
			{
				$issueTag   = 'jdown';
				$siteConfig = (object) [
					'gtmId'   => 'GTM-KR9CX8',
					'scripts' => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => '59300ad15992c776ad970068'
					],
					'cookies' => (object) [
						'performance' => 'true',
						'functional'  => 'false',
						'advertising' => 'false'
					]
				];

				break;
			}

			case 'exam.joomla.org':
			{
				$issueTag   = 'jexam';
				$siteConfig = (object) [
					'gtmId'   => 'GTM-TRG37W',
					'scripts' => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => '59300ad15992c776ad970068'
					],
					'cookies' => (object) [
						'performance' => 'true',
						'functional'  => 'false',
						'advertising' => 'false'
					]
				];

				break;
			}

			case 'extensions.joomla.org':
			{
				$siteConfig = (object) [
					'gtmId'    => 'GTM-MH6RGF',
					'scripts'  => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => '59300ad15992c776ad970068'
					],
					'cookies'  => (object) [
						'performance' => 'true',
						'functional'  => 'false',
						'advertising' => 'true'
					],
					'issueUrl' => 'https://github.com/joomla/jed-issues/issues/new?body=Please%20describe%20the%20problem%20or%20your%20issue'
				];

				break;
			}

			case 'forum.joomla.org':
			{
				$issueTag   = 'jforum';
				$siteConfig = (object) [
					'gtmId'   => 'GTM-TWSN2R',
					'scripts' => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => '59300ad15992c776ad970068'
					],
					'cookies' => (object) [
						'performance' => 'true',
						'functional'  => 'true',
						'advertising' => 'true'
					]
				];

				break;
			}

			case 'foundation.joomla.org':
			{
				$issueTag   = 'jfoundation';
				$siteConfig = (object) [
					'gtmId'   => 'false',
					'scripts' => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => '59300ad15992c776ad970068'
					],
					'cookies' => (object) [
						'performance' => 'false',
						'functional'  => 'false',
						'advertising' => 'false'
					]
				];

				break;
			}

			case 'framework.joomla.org':
			{
				$siteConfig = (object) [
					'gtmId'    => 'GTM-NX46ZP',
					'scripts'  => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => '59300ad15992c776ad970068'
					],
					'cookies'  => (object) [
						'performance' => 'true',
						'functional'  => 'false',
						'advertising' => 'false',
					],
					'issueUrl' => 'https://github.com/joomla/framework.joomla.org/issues/new?title=[FW%20Site]&body=Please%20state%20the%20nature%20of%20your%20development%20emergency'
				];

				break;
			}

			case 'help.joomla.org':
			{
				$siteConfig = (object) [
					'gtmId'   => 'GTM-NVGP9X',
					'scripts' => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => '59300ad15992c776ad970068'
					],
					'cookies' => (object) [
						'performance' => 'true',
						'functional'  => 'false',
						'advertising' => 'false'
					]
				];

				break;
			}

			case 'identity.joomla.org':
			{
				$siteConfig = (object) [
					'gtmId'    => 'GTM-5BL9XHS',
					'scripts'  => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => 'undefined'
					],
					'cookies'  => (object) [
						'performance' => 'false',
						'functional'  => 'false',
						'advertising' => 'false'
					],
					'issueUrl' => 'https://github.com/joomla/identity.joomla.org/issues/new?body=Please%20describe%20the%20problem%20or%20your%20issue'
				];

				break;
			}

			case 'issues.joomla.org':
			{
				$siteConfig = (object) [
					'gtmId'    => 'GTM-M7HXQ7',
					'scripts'  => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => '59300ad15992c776ad970068'
					],
					'cookies'  => (object) [
						'performance' => 'true',
						'functional'  => 'false',
						'advertising' => 'false'
					],
					'issueUrl' => 'https://issues.joomla.org/tracker/jtracker'
				];

				break;
			}

			case 'magazine.joomla.org':
			{
				$issueTag   = 'jcm';
				$siteConfig = (object) [
					'gtmId'   => 'GTM-WG7372',
					'scripts' => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => '59300ad15992c776ad970068'
					],
					'cookies' => (object) [
						'performance' => 'true',
						'functional'  => 'false',
						'advertising' => 'true'
					]
				];

				break;
			}

			case 'opensourcematters.org':
			{
				$issueTag   = 'josm';
				$siteConfig = (object) [
					'gtmId'   => 'GTM-5GST4C',
					'scripts' => (object) [
						'uaId'      => '',
						'awId'      => '',
						'twitter'   => 'true',
						'fbSdk'     => '',
						'fbPixel'   => '',
						'carbonads' => '',
						'addthisId' => '',
						'pingdomId' => ''
					],
					'cookies' => (object) [
						'performance' => 'true',
						'functional'  => 'false',
						'advertising' => 'true'
					]
				];

				break;
			}

			case 'resources.joomla.org':
			{
				$issueTag   = 'jrd';
				$siteConfig = (object) [
					'gtmId'   => 'GTM-K8CR7K',
					'scripts' => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => '59300ad15992c776ad970068'
					],
					'cookies' => (object) [
						'performance' => 'true',
						'functional'  => 'false',
						'advertising' => 'true'
					]
				];

				break;
			}

			case 'showcase.joomla.org':
			{
				$issueTag   = 'jshow';
				$siteConfig = (object) [
					'gtmId'   => 'GTM-NKT9FP',
					'scripts' => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => '59300ad15992c776ad970068'
					],
					'cookies' => (object) [
						'performance' => 'true',
						'functional'  => 'false',
						'advertising' => 'true'
					]
				];

				break;
			}

			case 'tm.joomla.org':
			{
				$issueTag   = 'jtm';
				$siteConfig = (object) [
					'gtmId'   => 'GTM-KZ7SM9',
					'scripts' => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => '59300ad15992c776ad970068'
					],
					'cookies' => (object) [
						'performance' => 'true',
						'functional'  => 'false',
						'advertising' => 'false',
					]
				];

				break;
			}

			case 'update.joomla.org':
			{
				$siteConfig = (object) [
					'gtmId'   => 'false',
					'scripts' => null,
					'cookies' => null
				];

				break;
			}

			case 'vel.joomla.org':
			{
				$issueTag   = 'jvel';
				$siteConfig = (object) [
					'gtmId'   => 'GTM-NKZPKQ',
					'scripts' => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'facebook-jssdk',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => '59300ad15992c776ad970068'
					],
					'cookies' => (object) [
						'performance' => 'true',
						'functional'  => 'false',
						'advertising' => 'false'
					]
				];

				break;
			}

			case 'volunteers.joomla.org':
			{
				$siteConfig = (object) [
					'gtmId'    => 'GTM-P2Z55T',
					'scripts'  => (object) [
						'uaId'      => 'undefined',
						'awId'      => 'undefined',
						'twitter'   => 'undefined',
						'fbSdk'     => 'undefined',
						'fbPixel'   => 'undefined',
						'carbonads' => 'undefined',
						'addthisId' => 'undefined',
						'pingdomId' => '59300ad15992c776ad970068'
					],
					'cookies'  => (object) [
						'performance' => 'true',
						'functional'  => 'false',
						'advertising' => 'false'
					],
					'issueUrl' => 'https://github.com/joomla/volunteers.joomla.org/issues/new?body=Please%20describe%20the%20problem%20or%20your%20issue'
				];

				break;
			}

			case 'www.joomla.org':
			{
				$issueTag   = 'joomla.org';
				$siteConfig = (object) [
					'gtmId'   => 'GTM-WWC8WL',
					'scripts' => null,
					'cookies' => (object) [
						'performance' => 'true',
						'functional'  => 'true',
						'advertising' => 'true'
					],
				];

				break;
			}

			default:
				$issueTag   = '';
				$siteConfig = (object) [
					'gtmId'   => 'false',
					'scripts' => null,
					'cookies' => null
				];

				break;
		}

		// Build the URL if we aren't using a custom source
		if (!isset($siteConfig->issueUrl))
		{
			$siteConfig->issueUrl = 'https://github.com/joomla/joomla-websites/issues/new?';

			// Do we have a tag?
			if (!empty($issueTag))
			{
				$siteConfig->issueUrl .= "title=[$issueTag]%20&";
			}

			$siteConfig->issueUrl .= 'body=Please%20describe%20the%20problem%20or%20your%20issue';
		}

		return $siteConfig;
	}

	/**
	 * Get the route for the login page
	 *
	 * @return  string
	 */
	public static function getLoginRoute()
	{
		// Load the com_users route helper
		JLoader::register('UsersHelperRoute', JPATH_SITE . '/components/com_users/helpers/route.php');

		// Look for a menu item for this route
		$itemid = UsersHelperRoute::getLoginRoute();
		$itemid = $itemid !== null ? '&Itemid=' . $itemid : '';

		// Return the base route plus menu item ID if available
		return 'index.php?option=com_users&view=login' . $itemid;
	}

	/**
	 * Load the template's footer section
	 *
	 * @param   string   $lang    The language to request
	 * @param   boolean  $useCdn  True to load resource from the cdn, false from local instance
	 *
	 * @return  string
	 */
	public static function getTemplateFooter($lang, $useCdn = true)
	{
		$result = self::loadTemplateSection('footer', $lang, $useCdn);

		// Check for an error
		if ($result === 'Could not load template section.')
		{
			return $result;
		}

		// Replace the placeholders and return the result
		return strtr(
			$result,
			[
				'%reportroute%' => static::getSiteConfig(Uri::getInstance()->toString(['host']))->issueUrl,
				'%loginroute%'  => Route::_(static::getLoginRoute()),
				'%logintext%'   => Factory::getUser()->guest ? Text::_('TPL_JOOMLA_FOOTER_LINK_LOG_IN') : Text::_('TPL_JOOMLA_FOOTER_LINK_LOG_OUT'),
				'%currentyear%' => date('Y'),
			]
		);
	}

	/**
	 * Load the template's CDN menu section
	 *
	 * @param   string   $lang    The language to request
	 * @param   boolean  $useCdn  True to load resource from the cdn, false from local instance
	 *
	 * @return  string
	 */
	public static function getTemplateMenu($lang, $useCdn = true)
	{
		return self::loadTemplateSection('menu', $lang, $useCdn);
	}

	/**
	 * Load the template section, caching the result if needed
	 *
	 * @param   string   $section  The section to be loaded
	 * @param   string   $lang     The language to request
	 * @param   boolean  $useCdn   True to load resource from the cdn, false from local instance
	 *
	 * @return  string
	 */
	private static function loadTemplateSection($section, $lang, $useCdn = true)
	{
		if (JDEBUG || !$useCdn)
		{
			$path = dirname(__DIR__) . "/cdn/layouts/$section/$lang.$section.html";

			if (!file_exists($path))
			{
				$path = dirname(__DIR__) . "/cdn/layouts/$section/en-GB.$section.html";
			}

			return file_get_contents($path);
		}

		/** @var \Joomla\CMS\Cache\Controller\CallbackController $cache */
		$cache = Factory::getCache('tpl_joomla', 'callback');

		// This is always cached regardless of the site's global setting
		$cache->setCaching(true);

		// Cache this for one day
		$cache->setLifeTime(1440);

		// Build the remote URL
		$url = "https://cdn.joomla.org/template/renderer.php?section=$section&language=$lang";

		try
		{
			return $cache->get(
				function ($url) {
					// Set a very short timeout to try and not bring the site down
					$response = HttpFactory::getHttp()->get($url, [], 2);

					if ($response->code !== 200)
					{
						throw new RuntimeException('Could not load template section.');
					}

					return $response->body;
				},
				[$url],
				md5(__METHOD__ . $section . $lang)
			);
		}
		catch (RuntimeException $e)
		{
			return 'Could not load template section.';
		}
	}
}
