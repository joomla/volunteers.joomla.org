<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\Console\AkeebaBackup\Extension;

defined('_JEXEC') || die;

use Akeeba\Plugin\Console\AkeebaBackup\Command\CommandFactoryInterface;
use Joomla\Application\ApplicationEvents;
use Joomla\Application\Event\ApplicationEvent;
use Joomla\CMS\Application\ConsoleApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Filesystem\Folder;
use Throwable;

class AkeebaBackup extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  7.5.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Akeeba Backup CLI Command Factory object instance.
	 *
	 * @var   CommandFactoryInterface
	 * @since 9.0.0
	 */
	protected $commandFactory;

	public function setCLICommandFactory(CommandFactoryInterface $factory)
	{
		$this->commandFactory = $factory;
	}

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   7.5.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			ApplicationEvents::BEFORE_EXECUTE => 'registerCLICommands',
		];
	}

	/**
	 * Registers command classes to the CLI application.
	 *
	 * This is an event handled for the ApplicationEvents::BEFORE_EXECUTE event.
	 *
	 * @param   ApplicationEvent  $event  The before_execite application event being handled
	 *
	 * @since        7.5.0
	 *
	 * @noinspection PhpUnused
	 */
	public function registerCLICommands(ApplicationEvent $event)
	{
		/** @var ConsoleApplication $app */
		$app = $event->getApplication();

		// Load the component language files
		$lang = $app->getLanguage();
		$lang->load('com_akeebabackup', JPATH_ADMINISTRATOR);
		$lang->load('com_akeebabackup', JPATH_SITE);

		// Only register CLI commands if Akeeba Backup is installed and enabled
		try
		{
			if (!ComponentHelper::isEnabled('com_akeebabackup'))
			{
				return;
			}
		}
		catch (Throwable $e)
		{
			return;
		}

		// Try to find all commands in the CliCommands directory of the component
		try
		{
			$files = Folder::files(JPATH_ADMINISTRATOR . '/components/com_akeebabackup/src/CliCommands', '.php');
		}
		catch (\Exception $e)
		{
			$files = [];
		}
		$files         = is_array($files) ? $files : [];

		foreach ($files as $file)
		{
			/**
			 * Try to instantiate and register each command object, going through the Akeeba Backup CLI command factory.
			 *
			 * The try/catch block has a rationale behind it. We get the command name by removing the .php extension
			 * from the base name of the file. This is combined with the root namespace of CLI commands to construct the
			 * class FQN we will be trying to instantiate.
			 *
			 * However, some hosts create copies of the files e.g. copying or renaming FooBar.php to FooBar.01.php or
			 * even FooBar_01.php. This is something we've seen and documented since 2013, mostly attributed to some
			 * hosts' really broken file scanners. These files would create invalid class names. Since these class names
			 * do not exist, trying to instantiate them will fail with a RuntimeException from the factory. We catch
			 * this and move on, ignoring the bad file.
			 *
			 * Further to that, it's possible that a different combination of unforeseen mistakes by the host and / or
			 * Joomla (e.g. not all files copied correctly on update) causing one or more CLI command classes to error
			 * out. This is why we are catching Throwable instead of just RuntimeException.
			 */
			try
			{
				$app->addCommand(
					$this->commandFactory->getCLICommand(basename($file, '.php'))
				);
			}
			catch (Throwable $e)
			{
			}
		}
	}
}
