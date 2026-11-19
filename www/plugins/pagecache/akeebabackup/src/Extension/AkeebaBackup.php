<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\PageCache\AkeebaBackup\Extension;

defined('_JEXEC') || die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

/**
 * Neutralises the "System – Page Cache" plugin's caching.
 *
 * @since 10.0.2
 */
class AkeebaBackup extends CMSPlugin implements SubscriberInterface
{
	public static function getSubscribedEvents(): array
	{
		return [
			'onPageCacheSetCaching' => 'onPageCacheSetCaching'
		];
	}

	public function onPageCacheSetCaching(Event $event)
	{
		$input = $this->getApplication()->getInput();

		if ($input->getCmd('option') !== 'com_akeebabackup')
		{
			return;
		}

		$result = $event->getArgument('result', []);

		$event->setArgument('result', array_merge($result, [false]));
	}
}