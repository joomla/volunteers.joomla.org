<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Utils;

defined('_JEXEC') || die;

use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Controller\CallbackController;
use Joomla\CMS\Cache\Exception\CacheExceptionInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;

/**
 * A utility class to help you quickly clean the Joomla! cache
 */
class CacheCleaner
{
	/**
	 * Clears the com_modules and com_plugins cache. You need to call this whenever you alter the publish state or
	 * parameters of a module or plugin from your code.
	 *
	 * @return  void
	 */
	public static function clearPluginsAndModulesCache()
	{
		self::clearPluginsCache();
		self::clearModulesCache();
	}

	/**
	 * Clears the com_plugins cache. You need to call this whenever you alter the publish state or parameters of a
	 * plugin from your code.
	 *
	 * @return  void
	 */
	public static function clearPluginsCache()
	{
		self::clearCacheGroups(['com_plugins'], [0, 1]);
	}

	/**
	 * Clears the com_modules cache. You need to call this whenever you alter the publish state or parameters of a
	 * module from your code.
	 *
	 * @return  void
	 */
	public static function clearModulesCache()
	{
		self::clearCacheGroups(['com_modules'], [0, 1]);
	}

	/**
	 * Clears the specified cache groups.
	 *
	 * @param   array        $clearGroups   Which cache groups to clear. Usually this is com_yourcomponent to clear
	 *                                      your component's cache.
	 * @param   array        $cacheClients  Which cache clients to clear. 0 is the back-end, 1 is the front-end. If you
	 *                                      do not specify anything, both cache clients will be cleared.
	 * @param   string|null  $event         An event to run upon trying to clear the cache. Empty string to disable. If
	 *                                      NULL and the group is "com_content" I will trigger onContentCleanCache.
	 *
	 * @return  void
	 * @throws  Exception
	 */
	public static function clearCacheGroups(array $clearGroups, array $cacheClients = [
		0, 1,
	], ?string $event = null): void
	{
		// Early return on nonsensical input
		if (empty($clearGroups) || empty($cacheClients))
		{
			return;
		}

		// Make sure I have a valid CMS application
		try
		{
			$app = Factory::getApplication();
		}
		catch (Exception $e)
		{
			return;
		}

		$isJoomla4 = version_compare(JVERSION, '3.9999.9999', 'gt');

		// Loop all groups to clean
		foreach ($clearGroups as $group)
		{
			// Groups must be non-empty strings
			if (empty($group) || !is_string($group))
			{
				continue;
			}

			// Loop all clients (applications)
			foreach ($cacheClients as $client_id)
			{
				$client_id = (int) ($client_id ?? 0);

				$options = $isJoomla4
					? self::clearCacheGroupJoomla4($group, $client_id, $app)
					: self::clearCacheGroupJoomla3($group, $client_id, $app);

				// Do not call any events if I failed to clean the cache using the core Joomla API
				if (!($options['result'] ?? false))
				{
					return;
				}

				/**
				 * If you're cleaning com_content and you have passed no event name I will use onContentCleanCache.
				 */
				if ($group === 'com_content')
				{
					$cacheCleaningEvent = $event ?: 'onContentCleanCache';
				}

				/**
				 * Call Joomla's cache cleaning plugin event (e.g. onContentCleanCache) as well.
				 *
				 * @see BaseDatabaseModel::cleanCache()
				 */
				if (empty($cacheCleaningEvent))
				{
					continue;
				}

				$app->triggerEvent($cacheCleaningEvent, $options);
			}
		}
	}

	/**
	 * Clean a cache group on Joomla 3
	 *
	 * @param   string          $group      The cache to clean, e.g. com_content
	 * @param   int             $client_id  The application ID for which the cache will be cleaned
	 * @param   CMSApplication  $app        The current CMS application
	 *
	 * @return  array Cache controller options, including cleaning result
	 * @throws  Exception
	 */
	private static function clearCacheGroupJoomla3(string $group, int $client_id, CMSApplication $app): array
	{
		$options = [
			'defaultgroup' => $group,
			'cachebase'    => ($client_id) ? JPATH_ADMINISTRATOR . '/cache' : $app->get('cache_path', JPATH_SITE . '/cache'),
			'result'       => true,
		];

		try
		{
			$cache = Cache::getInstance('callback', $options);
			/** @noinspection PhpUndefinedMethodInspection Available via __call(), not tagged in Joomla core */
			$cache->clean();
		}
		catch (Exception $e)
		{
			$options['result'] = false;
		}

		return $options;
	}

	/**
	 * Clean a cache group on Joomla 4
	 *
	 * @param   string          $group      The cache to clean, e.g. com_content
	 * @param   int             $client_id  The application ID for which the cache will be cleaned
	 * @param   CMSApplication  $app        The current CMS application
	 *
	 * @return  array Cache controller options, including cleaning result
	 * @throws  Exception
	 */
	private static function clearCacheGroupJoomla4(string $group, int $client_id, CMSApplication $app): array
	{
		// Get the default cache folder. Start by using the JPATH_CACHE constant.
		$cacheBaseDefault = JPATH_CACHE;

		// -- If we are asked to clean cache on the other side of the application we need to find a new cache base
		if ($client_id != $app->getClientId())
		{
			$cacheBaseDefault = (($client_id) ? JPATH_ADMINISTRATOR : JPATH_SITE) . '/cache';
		}

		// Get the cache controller's options
		$options = [
			'defaultgroup' => $group,
			'cachebase'    => $app->get('cache_path', $cacheBaseDefault),
			'result'       => true,
		];

		try
		{
			/** @var CallbackController $cache */
			$cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)->createCacheController('callback', $options);
			$cache->clean();
		}
		catch (CacheExceptionInterface $exception)
		{
			$options['result'] = false;
		}

		return $options;
	}
}
