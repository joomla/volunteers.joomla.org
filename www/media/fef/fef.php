<?php
/**
 * Akeeba Frontend Framework (FEF)
 *
 * @package   fef
 * @copyright (c) 2017-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 *
 * Created by Crystal Dionysopoulou for Akeeba Ltd, https://www.akeeba.com
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

@include_once (__DIR__ . '/version.php');

if (!defined('AKEEBAFEF_VERSION'))
{
	define('AKEEBAFEF_VERSION', 'dev');
	define('AKEEBAFEF_DATE', gmdate('Y-m-d'));
}

class AkeebaFEFHelper
{
	/**
	 * Media versioning tag
	 *
	 * @var  string
	 */
	public static $tag = null;

	/**
	 * Is FEF already loaded?
	 *
	 * @var  bool
	 */
	public static $loaded = false;

	/**
	 * Loads Akeeba Frontend Framework, both CSS and JS
	 *
	 * @param   bool  $withReset  Should I also load the CSS reset for the FEF container?
	 * @param   bool  $dark       Include Dark Mode CSS and JS?
	 *
	 * @return  void
	 */
	public static function load($withReset = true, $dark = false)
	{
		if (self::$loaded)
		{
			return;
		}

		if ($withReset)
		{
			self::loadCSS('css/fef-reset.min.css');
		}

		self::loadCSS('fef/fef-joomla.min.css');
		self::loadJS('fef/tabs.min.js');
		self::loadJS('fef/dropdown.min.js');

		if ($dark)
		{
			self::loadCSS('fef/dark.min.css');
			self::loadJS('fef/darkmode.min.js');
		}

		self::$loaded = true;
	}

	/**
	 * Helper method to load a JavaScript file using the ever-changing Joomla! API.
	 *
	 * Special considerations:
	 *
	 * Always use the minified version of the file. Joomla! will autoamtically use the non-minified one if Debug Site is
	 * enabled. If you use a .min.js extension the non-minified file is expected to have a .js extension. If your
	 * minified file has a plain .js extension then the non-minified file will be called <original name>-uncompressed.js
	 *
	 * You can have browser-specific files, e.g. foo_firefox.min.js, foo_firefox_57.min.js etc. These are loaded
	 * automatically instead of the foo.js file as needed.
	 *
	 * This method goes through Joomla's script loader, thus allowing template media overrides. The media overrides are
	 * supposed to be in the templates/YOUR_TEMPLATE/js/fef folder for FEF.
	 *
	 * @param string $file The Joomla!-coded path of the file, e.g. 'foo/bar.min.js' for the JavaScript file
	 *                     media/foo/js/bar.min.js
	 *
	 * @return void
	 */
	protected static function loadJS($file)
	{
		if (version_compare(JVERSION, '3.6.999', 'le'))
		{
			JHtml::_('script', $file, [
				'version'     => self::getMediaVersion(),
				'relative'    => true,
				'detectDebug' => true,
			], false, false, false, true);
		}
		// Joomla! 3.7 is broken. We have to use the new method AND MAKE SURE $attribs IS NOT EMPTY.
		else
		{
			JHtml::_('script', $file, [
				'version'       => self::getMediaVersion(),
				'relative'      => true,
				'detectDebug'   => true,
				'framework'     => false,
				'pathOnly'      => false,
				'detectBrowser' => true,
			], [
				'defer' => false,
				'async' => false,
			]);
		}
	}

	/**
	 * Helper method to load a CSS file using the ever-changing Joomla! API.
	 *
	 * Special considerations:
	 *
	 * Always use the minified version of the file. Joomla! will autoamtically use the non-minified one if Debug Site is
	 * enabled. If you use a .min.css extension the non-minified file is expected to have a .css extension. If your
	 * minified file has a plain .css extension then the non-minified file will be called
	 * <original name>-uncompressed.css
	 *
	 * You can have browser-specific files, e.g. foo_firefox.min.css, foo_firefox_57.min.css etc. These are loaded
	 * automatically instead of the foo.css file as needed.
	 *
	 * This method goes through Joomla's script loader, thus allowing template media overrides. The media overrides are
	 * supposed to be in the templates/YOUR_TEMPLATE/css/fef folder for FEF.
	 *
	 * @param string $file The Joomla!-coded path of the file, e.g. 'foo/bar.min.js' for the JavaScript file
	 *                     media/foo/js/bar.min.js
	 *
	 * @return void
	 */
	protected static function loadCSS($file)
	{
		if (version_compare(JVERSION, '3.6.999', 'le'))
		{
			JHtml::_('stylesheet', $file, [
				'version'     => self::getMediaVersion(),
				'relative'    => true,
				'detectDebug' => true,
			], true, false, false, true);
		}
		// Joomla! 3.7 is broken. We have to use the new method AND MAKE SURE $attribs IS NOT EMPTY.
		else
		{
			JHtml::_('stylesheet', $file, [
				'version'       => self::getMediaVersion(),
				'relative'      => true,
				'detectDebug'   => true,
				'pathOnly'      => false,
				'detectBrowser' => true,
			], [
				'type' => 'text/css',
			]);
		}
	}

	/**
	 * Get the media versioning tag. If it's not set, create one first.
	 *
	 * @return string
	 */
	protected static function getMediaVersion()
	{
		if (empty(self::$tag))
		{
			self::$tag = md5(AKEEBAFEF_VERSION . AKEEBAFEF_DATE . self::getApplicationSecret());
		}

		return self::$tag;
	}

	/**
	 * Return the secret key for the Joomla! installation. If we cannot get access to it we return the MD5 of the
	 * current file's modification date and time. This is enough to obfuscate the media version enough to make sure that
	 * two identical versions on two different sites will yield a different media version.
	 *
	 * @return mixed|string
	 */
	protected static function getApplicationSecret()
	{
		// Get the site's secret
		try
		{
			$app = \JFactory::getApplication();

			if (method_exists($app, 'get'))
			{
				$secret = $app->get('secret');
			}
		}
		catch (\Exception $e)
		{
			$secret = md5(filemtime(__FILE__));
		}

		return $secret;
	}
}
