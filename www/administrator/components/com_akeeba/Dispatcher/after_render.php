<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
use Joomla\CMS\Factory;

defined('_JEXEC') || die();

if (function_exists('akeebaBackupOnAfterRenderToFixBrokenCloudFlareRocketLoader'))
{
	return;
}

/**
 * Executive summary:
 *
 * CloudFlare has an optional feature called Rocket Loader. Effectively, this feature makes all JavaScript files of the
 * site load asynchronously even when the developer has EXPLICITLY stated this shouldn't be the case, e.g. when one
 * script depends on another. The end result is that it breaks your site.
 *
 * This file here registers a Joomla plugin event which disables Rocket Loader on all scripts loaded on the page. This
 * is necessary since Akeeba Backup's script files depend on Joomla's script files and there is no good, reliable and
 * forward compatbile way to isolate / distinguish these from any third party script files.
 *
 * Longer explanation:
 *
 * CloudFlare has a feature called Rocket Loader. Ostensibly, this feature improves the performance of your site by
 * "optimizing" the loading of JavaScript script files. For their very optimistic and entirely unrealistic description
 * please refer to their documentation link:
 * https://support.cloudflare.com/hc/en-us/articles/200168056-What-does-Rocket-Loader-do-
 *
 * Unfortunately, the way it's implemented is that it effectively makes ALL JavaScript files load as though they had
 * the
 * "async" attribute. In other words, they will load in a random order. This is a major problem for a general purpose
 * CMS like Joomla because extensions' JavaScript scripts depend not only on other script files from the same extension
 * but also on the core script files provided by Joomla itself. By loading these files in a random order it's possible
 * that Joomla core scripts load AFTER the extension's scripts, meaning the extension appears to be broken EVEN THOUGH
 * ITS DEVELOPER HAS (CORRECTLY) MADE SURE THAT JOOMLA CORE JAVASCRIPT IS LOADED BEFORE THEIR OWN SCRIPTS.
 *
 * For a description of what Rocket Loader REALLY does please read this:
 * http://webmasters.stackexchange.com/a/60277
 * As this person succinctly puts it: "I'm actually quite shocked that it works (although perhaps it doesn't always)".
 *
 * In any case, their documentation
 * (https://support.cloudflare.com/hc/en-us/articles/200169436--How-can-I-have-Rocket-Loader-ignore-my-script-s-in-Automatic-Mode-)
 * suggests adding the non-standard `data-cfasync="false"` attribute BEFORE the script's src attribute.
 *
 * Here's the thing. Joomla! does NOT let you do that thorugh its API used to register script files to be loaded in the
 * document. We can't bypass Joomla's API because this would violate Joomla best practices and possibly introduce new,
 * hard-to-impossible to debug issues.
 *
 * We can, however, work around CloudFlare's issue and Joomla's limitation the hard way. It's a "trick" we had used in
 * the past (Joomla 1.5 to 2.5), when Joomla didn't allow us to load certain CSS and JS files in the correct order.
 * Namely, we register an onAfterRender Joomla event handler for the function below. This function parses Joomla!'s
 * HTML output and forcibly add the special data-cfasync="false" attribute to script tags using a regular expression
 * search & replace.
 *
 * You might wonder why we apply this solution on ALL script tags with a src attribute, not just those in the <head>
 * considering that Akeeba Backup's scripts are only to be found in the head. Well, yeah, we DO ask Joomla to put our
 * JS files in the head... but there are third party plugins which override that and put them right above </body> along
 * with every other JS file on the page. Unscrupulous developers market them as "speed optimizers" without properly
 * explaining the problems they cause. So we get to deal with them the VERY hard way...
 *
 * While normally this would require a system plugin to hook into Joomla's onAfterRender event, we can't do that. So we
 * do the next best thing: abuse Joomla's event system to register the even handler directly, without going through
 * Joomla's plugin management subsystem.
 *
 * Frankly, this sort of black magic is exactly what I was happy to had gotten rid of in Akeeba Backup 5.0 because it
 * has a performance impact and it's rather precarious (Joomla 5.0 will require a rewrite of this code as a proper
 * Event class). Unforunately, the way CloudFlare Rocket Launcher works necessitates the perpetuation of arcane
 * solutions like this.
 *
 * Finally, for what it's worth, having evaluated CloudFlare Rocket Launcher extensively I recommend AGAINST using it
 * for Joomla sites. You will run into a multitude of problems. Same goes for any generic PHP application, e.g.
 * WordPress, PrestaShop etc. Rocket Launcher only makes sense on bespoke sites loading independent JavaScript bundle
 * files. It's NOT a good solution for generic sites.
 *
 * @return  void
 *
 * @since  5.1.3
 */
function akeebaBackupOnAfterRenderToFixBrokenCloudFlareRocketLoader()
{
	// The generated HTML
	try
	{
		$app = Factory::getApplication();
	}
	catch (Exception $e)
	{
		return;
	}

	$buffer = $app->getBody();

	// Replace '<script...src' with '<script...data-cfasync="false" src'
	$regEx  = '/<script([^>]*)src\s?=\s?(\'|")/im';
	$buffer = preg_replace($regEx, '<script$1 data-cfasync="false" src=$2', $buffer);

	// Reconstruct the page's HTML and set it back to the buffer
	$app->setBody($buffer);
}
