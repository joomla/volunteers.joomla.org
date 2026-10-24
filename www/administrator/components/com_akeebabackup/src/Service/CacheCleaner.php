<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Service;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\RunPluginsTrait;
use Exception;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Throwable;

class CacheCleaner
{
	use RunPluginsTrait;

	protected $app;

	protected $cacheControllerFactory;

	public function __construct(CMSApplicationInterface $app, CacheControllerFactoryInterface $cacheControllerFactory)
	{
		$this->app                    = $app;
		$this->cacheControllerFactory = $cacheControllerFactory;
	}

	/**
	 * Clean a cache group
	 *
	 * @param   string  $group      The cache to clean, e.g. com_content
	 * @param   int     $client_id  The application ID for which the cache will be cleaned
	 * @param   object  $app        The current CMS application. DO NOT TYPEHINT MORE SPECIFICALLY!
	 *
	 * @return  array Cache controller options, including cleaning result
	 * @throws  Exception
	 */
	public function clearGroup(string $group): array
	{
		$options = [
			'defaultgroup' => $group,
			'cachebase'    => $this->app->get('cache_path', JPATH_CACHE),
			'result'       => true,
		];

		try
		{
			$this->cacheControllerFactory
				->createCacheController('callback', $options)
				->cache
				->clean();
		}
		catch (Throwable $e)
		{
			$options['result'] = false;
		}

		return $options;
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
	public function clearGroups(array $clearGroups, ?string $event = null): void
	{
		// Early return on nonsensical input
		if (empty($clearGroups))
		{
			return;
		}

		// Loop all groups to clean
		foreach ($clearGroups as $group)
		{
			// Groups must be non-empty strings
			if (empty($group) || !is_string($group))
			{
				continue;
			}

			$options = $this->clearGroup($group);

			// Do not call any events if I failed to clean the cache using the core Joomla API
			if (!($options['result'] ?? false))
			{
				return;
			}

			/**
			 * If you're cleaning com_content, and you have passed no event name I will use onContentCleanCache.
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

			$this->triggerPluginEvent($cacheCleaningEvent, $options);
		}
	}
}