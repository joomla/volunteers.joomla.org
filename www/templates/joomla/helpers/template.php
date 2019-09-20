<?php
/**
 * Joomla.org site template
 *
 * @copyright   Copyright (C) 2005 - 2017 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */
 
//volunteers.joomla.org/www/templates/joomla/helpers/template.php


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
	 * Retrieve the Google Tag Manager property ID for the current site
	 *
	 * Note that this helper method is only 'good' for live sites, for development environments no ID is returned
	 *
	 * @param   string  $siteUrl  The site URL without the scheme
	 *
	 * @return  string|boolean  The property ID or boolean false if one is not assigned
	 */
	public static function getGtmId($siteUrl)
	{
		switch ($siteUrl)
		{
			case 'api.joomla.org':
			{
				$id = 'GTM-NDWJB8';

				break;
			}

			case 'certification.joomla.org':
			{
				$id = 'GTM-PFP9MJ';

				break;
			}

			case 'community.joomla.org':
			{
				$id = 'GTM-WQNG7Z';

				break;
			}

			case 'conference.joomla.org':
			{
				$id = 'GTM-PZWNZR';

				break;
			}

			case 'developer.joomla.org':
			{
				$id = 'GTM-WJ36D4';

				break;
			}

			case 'docs.joomla.org':
			{
				$id = 'GTM-K6SPGS';

				break;
			}

			case 'domains.joomla.org': // @TODO add GTM if exists, else return null

			case 'downloads.joomla.org':
			{
				$id = 'GTM-KR9CX8';

				break;
			}


			case 'exam.joomla.org':
			{
				$id = 'GTM-TRG37W';

				break;
			}

			case 'extensions.joomla.org':
			{
				$id = 'GTM-MH6RGF';

				break;
			}

			case 'forum.joomla.org':
			{
				$id = 'GTM-TWSN2R';

				break;
			}

			case 'foundation.joomla.org':
			{
				$id = false;

				break;
			}

			case 'framework.joomla.org':
			{
				$id = 'GTM-NX46ZP';

				break;
			}

			case 'help.joomla.org':
			{
				$id = 'GTM-NVGP9X';

				break;
			}

			case 'issues.joomla.org':
			{
				$id = 'GTM-M7HXQ7';

				break;
			}

			case 'launch.joomla.org':
			{
				$id = 'GTM-T253JLF';

				break;
			}

			case 'magazine.joomla.org':
			{
				$id = 'GTM-WG7372';

				break;
			}

			case 'opensourcematters.org':
			{
				$id = 'GTM-5GST4C';

				break;
			}

			case 'resources.joomla.org':
			{
				$id = 'GTM-K8CR7K';

				break;
			}

			case 'showcase.joomla.org':
			{
				$id = 'GTM-NKT9FP';

				break;
			}

			case 'tm.joomla.org':
			{
				$id = 'GTM-KZ7SM9';

				break;
			}

			case 'update.joomla.org':
			{
				$id = false;

				break;
			}

			case 'vel.joomla.org':
			{
				$id = 'GTM-NKZPKQ';

				break;
			}

			case 'volunteers.joomla.org':
			{
				$id = 'GTM-P2Z55T';

				break;
			}

			case 'shop.joomla.org':
			{
				$id = 'GTM-WQNG7Z';

				break;
			}

			case 'test.volunteers.joomla.org': // @TODO remove after testing
			case 'volunteers.joomla.test': // @TODO remove after testing
			{
				$id = 'GTM-P2Z55T';

				break;
			}

			case 'www.joomla.org':
			{
				$id = 'GTM-WWC8WL';

				break;
			}

			default:
				$id = false;

				break;
		}

		return $id;
	}

	/**
	 * Retrieve the "report an issue" link for the current site
	 *
	 * Note that this helper method is only 'good' for the live site, for development environments it will use a default link
	 *
	 * @param   string  $siteUrl  The site URL without the scheme
	 *
	 * @return  string  The issue link
	 */
	public static function getIssueLink($siteUrl)
	{
		$hasCustom = false;

		switch ($siteUrl)
		{
			case 'api.joomla.org':
			{
				$tag = 'japi';

				break;
			}

			case 'certification.joomla.org':
			{
				$tag = 'jcertif';

				break;
			}

			case 'community.joomla.org':
			{
				$tag = 'jcomm';

				break;
			}

			case 'conference.joomla.org':
			{
				$tag = 'jconf';

				break;
			}

			case 'developer.joomla.org':
			{
				$tag = 'jdev';

				break;
			}

			case 'docs.joomla.org':
			{
				$tag = 'jdocs';

				break;
			}

			case 'downloads.joomla.org':
			{
				$tag = 'jdown';

				break;
			}


			case 'exam.joomla.org':
			{
				$tag = 'jexam';

				break;
			}

			case 'extensions.joomla.org':
			{
				$hasCustom = true;
				$tag       = 'jed';
				$url       = 'https://github.com/joomla/jed-issues/issues/new?body=Please%20describe%20the%20problem%20or%20your%20issue';

				break;
			}

			case 'forum.joomla.org':
			{
				$tag = 'jforum';

				break;
			}

			case 'framework.joomla.org':
			{
				$hasCustom = true;
				$tag       = 'jfw';
				$url       = 'https://github.com/joomla/framework.joomla.org/issues/new?title=[FW%20Site]&body=Please%20state%20the%20nature%20of%20your%20development%20emergency';

				break;
			}

			case 'issues.joomla.org':
			{
				$hasCustom = true;
				$tag       = 'jissues';
				$url       = 'https://issues.joomla.org/tracker/jtracker';

				break;
			}

			case 'magazine.joomla.org':
			{
				$tag = 'jcm';

				break;
			}

			case 'opensourcematters.org':
			{
				$tag = 'josm';

				break;
			}

			case 'resources.joomla.org':
			{
				$tag = 'jrd';

				break;
			}

			case 'showcase.joomla.org':
			{
				$tag = 'jshow';

				break;
			}

			case 'tm.joomla.org':
			{
				$tag = 'jtm';

				break;
			}

			case 'vel.joomla.org':
			{
				$tag = 'jvel';

				break;
			}

			case 'volunteers.joomla.org':
			{
				$hasCustom = true;
				$tag       = 'jvols';
				$url       = 'https://github.com/joomla/volunteers.joomla.org/issues/new?body=Please%20describe%20the%20problem%20or%20your%20issue';

				break;
			}

			case 'www.joomla.org':
			{
				$tag = 'joomla.org';

				break;
			}

			default:
				$tag = '';

				break;
		}

		// Build the URL if we aren't using a custom source
		if (!$hasCustom)
		{
			$url = 'https://github.com/joomla/joomla-websites/issues/new?';

			// Do we have a tag?
			if (!empty($tag))
			{
				$url .= "title=[$tag]%20&";
			}

			$url .= 'body=Please%20describe%20the%20problem%20or%20your%20issue';
		}

		return $url;
	}

	/**
	 * Retrieve all script related IDs and relative information where an ID is not applied (i.e. 'true' for Twitter) for the current site
	 *
	 * Note that this helper method is only 'good' for live sites, for development environments since no ID is returned, either script related IDs do
	 *
	 * @param   string  $gtmId  The property's GTM Identifier
	 *
	 * @return  object  $ids    The property's script IDs with a boolean status. In case of GTM ID's non-existence, the object's status and values are set to false
	 */
	public static function getScriptIds($gtmId)
	{
		$ids = new stdClass();

		switch ($gtmId)
		{
			case 'GTM-NDWJB8': // @origin api.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = '59300ad15992c776ad970068';

				break;
			}

			case 'GTM-PFP9MJ': // @origin certification.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = '59300ad15992c776ad970068';

				break;
			}

			case 'GTM-WQNG7Z': // @origin community.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = true;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = '59300ad15992c776ad970068';

				break;
			}

			case 'GTM-PZWNZR': // @origin conference.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = 'undefined';

				break;
			}

			case 'GTM-WJ36D4': // @origin developer.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = '59300ad15992c776ad970068';

				break;
			}

			case 'GTM-K6SPGS': // @origin docs.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = true;
				$ids->addthisId = 'ra-5378f70766e02197';
				$ids->pingdomId = 'undefined';

				break;
			}

			case 'GTM-KR9CX8': // @origin downloads.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = '59300ad15992c776ad970068';

				break;
			}

			case 'GTM-TRG37W': // @origin exam.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = '59300ad15992c776ad970068';

				break;
			}

			case 'GTM-MH6RGF': // @origin extensions.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = '59300ad15992c776ad970068';

				break;
			}

			case 'GTM-TWSN2R': // @origin forum.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = '59300ad15992c776ad970068';

				break;
			}

			case 'GTM-NX46ZP': // @origin framework.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = '59300ad15992c776ad970068';

				break;
			}

			case 'GTM-NVGP9X': // @origin help.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = '59300ad15992c776ad970068';

				break;
			}

			case 'GTM-M7HXQ7': // @origin issues.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = '59300ad15992c776ad970068';

				break;
			}

			case 'GTM-T253JLF': // @origin launch.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'UA-28160972-3';
				$ids->awId      = 'AW-976618339';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = 'undefined';

				break;
			}

			case 'GTM-WG7372': // @origin magazine.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = '59300ad15992c776ad970068';

				break;
			}

			case 'GTM-5GST4C': // @origin www.opensourcematters.org
			{
				$ids->status    = true;
				$ids->uaId      = '';
				$ids->awId      = '';
				$ids->twitter   = 'true';
				$ids->fbId      = '';
				$ids->addthis   = 'true';
				$ids->addthisId = '';
				$ids->pingdomId = '';

				break;
			}

			case 'GTM-K8CR7K': // @origin resources.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = '59300ad15992c776ad970068';

				break;
			}

			case 'GTM-NKT9FP': // @origin showcase.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = '59300ad15992c776ad970068';

				break;
			}

			case 'GTM-KZ7SM9': // @origin tm.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = '59300ad15992c776ad970068';

				break;
			}

			case 'GTM-NKZPKQ': // @origin vel.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'facebook-jssdk'; // @TODO should be checked for validity
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = '59300ad15992c776ad970068';

				break;
			}

			case 'GTM-WQNG7Z': // @origin shop.joomla.org | @TODO should consider to add a standalone cookie script
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = '59300ad15992c776ad970068';

				break;
			}

			case 'GTM-P2Z55T': // @origin volunteers.joomla.org
			{
				$ids->status    = true;
				$ids->uaId      = 'undefined';
				$ids->awId      = 'undefined';
				$ids->twitter   = false;
				$ids->fbId      = 'undefined';
				$ids->addthis   = false;
				$ids->addthisId = 'undefined';
				$ids->pingdomId = '59300ad15992c776ad970068';

				break;
			}

			default:
				$ids->status    = false;
				$ids->uaId      = false;
				$ids->awId      = false;
				$ids->twitter   = false;
				$ids->fbId      = false;
				$ids->addthis   = false;
				$ids->addthisId = false;
				$ids->pingdomId = false;

				break;
		}

		return $ids;
	}

	/**
	 * Retrieve the Cookie Categories Flag for each property
	 *
	 * Note that this helper method is only 'good' for live sites, for development environments no ID is returned
	 *
	 * @param   string  $siteUrl  The site URL without the scheme
	 *
	 * @return  object|boolean  The property's cookie category flags as a boolean true or false
	 */
	public static function getCcCategories($siteUrl)
	{
		$ccCategories = new stdClass();
		$ccCategories->status = true;

		switch ($siteUrl)
		{
			case 'api.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = false;

				break;
			}

			case 'certification.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = false;

				break;
			}

			case 'community.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = true;
				$ccCategories->Advertising = true;

				break;
			}

			case 'conference.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = true;
				$ccCategories->Advertising = true;

				break;
			}

			case 'developer.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = false;

				break;
			}

			case 'docs.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = true;
				$ccCategories->Advertising = true;

				break;
			}

			case 'domains.joomla.org': // @TODO GTM must be added first in func getGtmId($siteUrl)

			case 'downloads.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = false;

				break;
			}


			case 'exam.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = false;

				break;
			}

			case 'extensions.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = true;

				break;
			}

			case 'forum.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = true;
				$ccCategories->Advertising = true;

				break;
			}

			case 'foundation.joomla.org':
			{
				$ccCategories->Performance = false;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = false;

				break;
			}

			case 'framework.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = false;

				break;
			}

			case 'help.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = false;

				break;
			}

			case 'issues.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = false;

				break;
			}

			case 'launch.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = true;

				break;
			}

			case 'magazine.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = true;

				break;
			}

			case 'opensourcematters.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = true;

				break;
			}

			case 'resources.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = true;

				break;
			}

			case 'showcase.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = true;

				break;
			}

			case 'tm.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = false;

				break;
			}

			case 'update.joomla.org':
			{
				$ccCategories->Performance = false;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = false;

				break;
			}

			case 'vel.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = false;

				break;
			}

			case 'volunteers.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = false;

				break;
			}

			case 'shop.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = true;
				$ccCategories->Advertising = true;

				break;
			}

			case 'test.volunteers.joomla.org': // @TODO remove after testing
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = false;

				break;
			}

			case 'volunteers.joomla.test': // @TODO N/A
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = true;
				$ccCategories->Advertising = true;

				break;
			}

			case 'www.joomla.org':
			{
				$ccCategories->Performance = true;
				$ccCategories->Functional = true;
				$ccCategories->Advertising = true;

				break;
			}

			default:
				
				$ccCategories->Performance = false;
				$ccCategories->Functional = false;
				$ccCategories->Advertising = false;

				break;
		}

		return $ccCategories;
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
				'%reportroute%' => static::getIssueLink(Uri::getInstance()->toString(['host'])),
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
				function ($url)
				{
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
