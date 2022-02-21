<?php
/**
 * Akeeba Frontend Framework (FEF)
 *
 * @package   fef
 * @copyright (c) 2017-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
use Joomla\CMS\Document\PreloadManagerInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;

defined('_JEXEC') || die();

if (@file_exists(__DIR__ . '/version.php') && @is_file(__DIR__ . '/version.php') && @is_readable(__DIR__ . '/version.php'))
{
	@include_once(__DIR__ . '/version.php');
}

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
	 * @var   string
	 * @since 1.0.3
	 */
	public static $tag = null;

	/**
	 * The Akeeba FEF Loader object
	 *
	 * @var AkeebaFEFLoader
	 * @since 2.0.0
	 */
	private static $loader;

	/**
	 * Is this Joomla 4?
	 *
	 * @var   bool|null
	 * @since 2.0.0
	 */
	private static $isJoomla4 = null;

	/**
	 * Loads the Akeeba Frontend Framework, both CSS and JS
	 *
	 * @param   bool  $withReset  Should I also load the CSS reset for the FEF container?
	 * @param   bool  $dark       Include Dark Mode CSS?
	 *
	 * @return  void
	 * @since   1.0.3
	 */
	public static function load(bool $withReset = true, bool $dark = false)
	{
		self::loadCSSFramework();
		self::loadJSFramework();
	}

	/**
	 * Loads the Akeeba FEF CSS Framework
	 *
	 * @param   bool  $withReset  Should I also load the CSS reset for the FEF container?
	 * @param   bool  $dark       Include Dark Mode CSS?
	 *
	 * @return  void
	 * @since   1.0.3
	 */
	public static function loadCSSFramework(bool $withReset = true, bool $dark = false)
	{
		self::getLoader()->loadCSSFramework($withReset, $dark);
	}

	/**
	 * Loads the Akeeba FEF JavaScript Framework
	 *
	 * @param   bool  $minimal  Should I load the minimal framework (without optional features linked to FEF CSS?)
	 *
	 * @return  void
	 * @since   2.0.0
	 */
	public static function loadJSFramework(bool $minimal = false)
	{
		self::getLoader()->loadJSFramework($minimal);
	}

	/**
	 * Legacy (FEF 1.1.x) alias to loadFEFScript.
	 *
	 * @param   string  $name
	 *
	 * @see        self::loadFEFScript
	 * @deprecated 3.0
	 * @since      1.1.0
	 */
	public static function loadScript(string $name): void
	{
		self::loadFEFScript($name, true);
	}

	/**
	 * Load an Akeeba FEF JavaScript file and its dependencies.
	 *
	 * @param   string  $name   The basename of the file, e.g. "Tabs"
	 * @param   bool    $defer  Should I defer loading of the file?
	 *
	 * @since   2.0.0
	 */
	public static function loadFEFScript(string $name, bool $defer = true): void
	{
		self::getLoader()->loadFEFScript($name, $defer);
	}

	/**
	 * Is this Joomla 4 or later?
	 *
	 * @return  bool
	 * @since   2.0.0
	 */
	private static function isJoomla4(): bool
	{
		if (!is_bool(self::$isJoomla4))
		{
			self::$isJoomla4 = version_compare(JVERSION, '3.999.999', 'gt');
		}

		return self::$isJoomla4;
	}

	/**
	 * Load a JavaScript file using the Joomla! API.
	 *
	 * Special considerations:
	 *
	 * We always load the minified version of the file. Joomla! will automatically use the non-minified one if Debug
	 * Site is enabled.
	 *
	 * You can have browser-specific files, e.g. foo_firefox.min.js, foo_firefox_57.min.js etc. These are loaded
	 * automatically instead of the foo.js file as needed.
	 *
	 * This method goes through Joomla's script loader, thus allowing template media overrides. The media overrides are
	 * supposed to be in the templates/YOUR_TEMPLATE/js/fef folder for FEF.
	 *
	 * @param   string  $name   The Joomla!-coded path of the file, e.g. 'foo/bar.min.js' for the JavaScript file
	 *                          media/foo/js/bar.min.js
	 *
	 * @param   bool    $defer  Should I load the script defered?
	 *
	 * @return  void
	 * @since   1.0.3
	 */
	private static function loadJS(string $name, bool $defer = true): void
	{
		$options = [
			'version'       => self::getMediaVersion(),
			'relative'      => true,
			'detectDebug'   => false,
			'framework'     => false,
			'pathOnly'      => false,
			'detectBrowser' => true,
		];
		HTMLHelper::_('script', 'fef/' . $name . '.min.js', $options, [
			'defer' => $defer,
			'async' => false,
		]);

		/**
		 * Preload the static resource we are definitely asking the browser to use for better performance.
		 *
		 * Yes, this is also useful for deferred scripts! On Joomla 4 this goes through Preload Manager which does an
		 * HTTP/2 Push for the script files. This is **far** faster than the browser making a number of HEAD requests
		 * to see if the scripts are cached then an equal (or smaller) number of GET requests to fetch the scripts.
		 */
		$options['pathOnly'] = 'true';
		$path                = HTMLHelper::_('script', 'fef/' . $name . '.min.js', $options);

		if (empty($path))
		{
			return;
		}

		self::preloadResource($path . '?' . self::getMediaVersion(), ['as' => 'script']);
	}

	/**
	 * Load a CSS file using the Joomla! API.
	 *
	 * Special considerations:
	 *
	 * We always as Joomla to load the minified version of a file. Joomla! will automatically use the non-minified one
	 * if Debug Site is enabled.
	 *
	 * You can have browser-specific files, e.g. foo_firefox.min.css, foo_firefox_57.min.css etc. These are loaded
	 * automatically instead of the foo.css file as needed.
	 *
	 * This method goes through Joomla's script loader, thus allowing template media overrides. The media overrides are
	 * supposed to be in the templates/YOUR_TEMPLATE/css/fef folder for FEF.
	 *
	 * We are instructing the browser to preload the CSS file we are inserting in the HTML. This can cause a small
	 * performance increase. On Joomla 4 the increase is most noticeable because we go through the Preload Manager
	 * which can leverage HTTP/2 Push.
	 *
	 * When loading the main CSS file (joomla-fef) we preload the WOFF font files which will be most likely used by the
	 * browser consuming the CSS. This allows the browser to fetch the font files before fully parsing the stylesheet,
	 * saving some time.
	 *
	 * @param   string  $name  The Joomla!-coded path of the file, e.g. 'foo/bar.min.css' for the stylesheet file
	 *                         media/foo/css/bar.min.css
	 *
	 * @return  void
	 * @since   1.0.3
	 */
	private static function loadCSS(string $name): void
	{
		/**
		 * IMPORTANT! The $attribs (final parameter) MUST ALWAYS be non-empty. Otherwise Joomla! 3.x bugs out.
		 */
		$options = [
			'version'       => self::getMediaVersion(),
			'relative'      => true,
			'detectDebug'   => false,
			'pathOnly'      => false,
			'detectBrowser' => true,
		];
		HTMLHelper::_('stylesheet', 'fef/' . $name . '.min.css', $options, [
			'type' => 'text/css',
		]);

		// Preload the static resource we are definitely asking the browser to use for better performance
		$options['pathOnly'] = 'true';
		$path                = HTMLHelper::_('stylesheet', 'fef/' . $name . '.min.css', $options, [
			'type' => 'text/css',
		]);

		if (empty($path))
		{
			return;
		}

		self::preloadResource($path . '?' . self::getMediaVersion(), ['as' => 'style']);

		// Special case: loading the fef-joomla stylesheet. Preload the font files as well
		if ($name == 'fef-joomla')
		{
			$fontPath = dirname(dirname($path)) . '/fonts/akeeba/Akeeba-Products.woff';
			self::preloadResource($fontPath, ['as' => 'font', 'crossorigin' => 'anonymous']);

			$fontPath = dirname(dirname($path)) . '/fonts/Ionicon/ionicons.woff';
			self::preloadResource($fontPath, ['as' => 'font', 'crossorigin' => 'anonymous']);
		}
	}

	/**
	 * Preload a resource.
	 *
	 * On Joomla 3 this adds a LINK tag to the HTML document, instructing the browser to preload the resource.
	 *
	 * On Joomla 4 we are using the document object's Preload Manager to leverage HTTP/2 Push if available.
	 *
	 * @param   string  $url      The absolute or relative URL of the resource to preload.
	 * @param   array   $options  Preload options. You need to specify 'as' as the bare minimum.
	 *
	 * @return  void
	 * @see     https://developer.mozilla.org/en-US/docs/Web/HTML/Preloading_content
	 *
	 * @since   2.0.0
	 */
	private static function preloadResource(string $url, array $options): void
	{
		if (!self::isJoomla4() || !self::preloadResourceJoomla4($url, $options))
		{
			self::preloadResourceJoomla3($url, $options);
		}
	}

	/**
	 * Preload a resource on Joomla 3.
	 *
	 * This adds a LINK tag to the HTML document, instructing the browser to preload the resource.
	 *
	 * @param   string  $url      The absolute or relative URL of the resource to preload.
	 * @param   array   $options  Preload options. You need to specify 'as' as the bare minimum.
	 *
	 * @return  bool  True if successful; false if we can't get the document, or can't add a LINK tag e.g. this is not
	 *                an HTMLDocument
	 * @since   2.0.0
	 */
	private static function preloadResourceJoomla3(string $url, array $options): bool
	{
		// Try to get Joomla's document object
		$document = self::getDocument();

		// Make sure the document object implements addCustomTag
		if (!is_object($document) || !method_exists($document, 'addCustomTag'))
		{
			return false;
		}

		$options['rel'] = 'preload';
		$options['href'] = self::relativeToAbsoluteURL($url);
		$document->addCustomTag('<link ' . ArrayHelper::toString($options) . '>');

		return true;
	}

	/**
	 * Preload a resource on Joomla 4.
	 *
	 * We are using the document object's Preload Manager to leverage HTTP/2 Push if available.
	 *
	 * @param   string  $url      The absolute or relative URL of the resource to preload.
	 * @param   array   $options  Preload options. You need to specify 'as' as the bare minimum.
	 *
	 * @return  bool  True if successful; false if we can't get the document, or can't add a LINK tag e.g. this is not
	 *                an HTMLDocument
	 * @since   2.0.0
	 */
	private static function preloadResourceJoomla4(string $url, array $options): bool
	{
		// Make sure we're in a version of Joomla which has support for the Preload Manager
		if (!interface_exists('\\Joomla\CMS\Document\PreloadManagerInterface'))
		{
			return false;
		}

		// Try to get Joomla's document object
		$document = self::getDocument();

		// Make sure the document object implements getPreloadManager
		if (!is_object($document) || !method_exists($document, 'getPreloadManager'))
		{
			return false;
		}

		// Try to get the preload manager
		try
		{
			$preloadManager = $document->getPreloadManager();
		}
		catch (Throwable $e)
		{
			return false;
		}

		// Make sure the preload manager is an object implementing the PreloadManagerInterface
		if (!is_object($preloadManager) || !($preloadManager instanceof PreloadManagerInterface))
		{
			return false;
		}

		$absoluteUrl = self::relativeToAbsoluteURL($url);
		$preloadManager->preload($absoluteUrl, $options);

		return true;
	}

	/**
	 * Get the Joomla document object
	 *
	 * @return JDocument|\Joomla\CMS\Document\Document|null  NULL if we can't retrieve the document object.
	 *
	 * @since  2.0.0
	 */
	private static function getDocument()
	{
		// Get the CMS application
		try
		{
			$app = Factory::getApplication();
		}
		catch (Throwable $e)
		{
			return null;
		}

		// Make sure it's an object implementing getDocument
		if (!is_object($app) || !method_exists($app, 'getDocument'))
		{
			return null;
		}

		// Try to get the document
		try
		{
			$document = $app->getDocument();
		}
		catch (Throwable $e)
		{
			return null;
		}

		return $document;
	}

	/**
	 * Convert a relative URL to an absolute URL for the current site
	 *
	 * @param   string  $url  The possibly relative URL.
	 *
	 * @return  string  The definitely absolute URL.
	 *
	 * @since   2.0.0
	 */
	private static function relativeToAbsoluteURL(string $url): string
	{
		static $baseUri;
		static $basePath;

		// Get the base URI, e.g. 'https://localhost/test'
		if (empty($baseUri))
		{
			$baseUri  = $baseUri ?? Uri::base();

			if (substr($baseUri, -15) === '/administrator/')
			{
				$baseUri = substr($baseUri, 0, -15);
			}
			elseif (substr($baseUri, -14) === '/administrator')
			{
				$baseUri = substr($baseUri, 0, -14);
			}
		}

		// Get the base path, e.g. 'test'
		if (empty($basePath))
		{
			$basePath = $basePath ?? Uri::base(true);
			$basePath = empty($basePath) ? '' : trim($basePath, '/');

			if ($basePath === 'administrator')
			{
				$basePath = '';
			}
			elseif (substr($basePath, -14) == '/administrator')
			{
				$basePath = trim(substr($basePath, 0, -14), '/');
			}
		}

		if ((substr($url, 0, 2) == '//') ||
			(substr($url, 0, 7) == 'http://') ||
			(substr($url, 0, 8) == 'https://') ||
			(substr($url, 0, strlen($baseUri)) == $baseUri))
		{
			return $url;
		}

		$url = ltrim($url, '/');

		if ((strlen($basePath) != 0) && (substr($url, 0, strlen($basePath)) === $basePath))
		{
			$url = ltrim(substr($url, strlen($basePath)), '/');
			$url = ltrim($url, '/');
		}

		return rtrim($baseUri, '/') . '/' . $url;
	}

	/**
	 * Get the media versioning tag. If it's not set, create one first.
	 *
	 * @return  string
	 * @since   1.1.0
	 */
	private static function getMediaVersion(): string
	{
		if (empty(self::$tag))
		{
			self::$tag = md5(AKEEBAFEF_VERSION . AKEEBAFEF_DATE . self::getApplicationSecret());
		}

		return self::$tag;
	}

	/**
	 * Return the secret key for the Joomla! installation. Falls back to an MD5 of our file mod time.
	 *
	 * @return  string
	 * @since   1.1.0
	 */
	private static function getApplicationSecret(): string
	{
		$secret = md5(filemtime(__FILE__));

		// Get the site's secret
		try
		{
			$app = Factory::getApplication();

			if (method_exists($app, 'get'))
			{
				return $app->get('secret', $secret);
			}
		}
		catch (Exception $e)
		{
		}

		return $secret;
	}

	/**
	 * Returns or creates the Akeeba FEF Loader object.
	 *
	 * IMPORTANT: DO NOT SET A RETURN VALUE TYPE HINT. The AkeebaFEFLoader class is undefined until we load it in this
	 *            method. This causes a chicken and egg problem which results in a Fatal Error!
	 *
	 * @return  AkeebaFEFLoader
	 * @since   2.0.0
	 */
	private static function getLoader()
	{
		if (!is_null(self::$loader))
		{
			return self::$loader;
		}

		if (!class_exists('AkeebaFEFLoader'))
		{
			require_once __DIR__ . '/php/AkeebaFEFLoader.php';
		}

		self::$loader = new AkeebaFEFLoader(function (string $name) {
			self::loadCSS($name);
		}, function (string $name, bool $defer) {
			self::loadJS($name, $defer);
		}, 'joomla');

		return self::$loader;
	}
}
