<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\Console\AkeebaBackup\Command;

defined('_JEXEC') || die;

use Joomla\Application\ApplicationInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\Console\Command\AbstractCommand;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;

class CommandFactory implements CommandFactoryInterface, DatabaseAwareInterface
{
	use MVCFactoryAwareTrait;
	use DatabaseAwareTrait;

	private ApplicationInterface $app;

	public function setApplication(ApplicationInterface $app)
	{
		$this->app = $app;
	}

	public function getCLICommand(string $commandName): AbstractCommand
	{
		$classFQN = 'Akeeba\\Component\\AkeebaBackup\\Administrator\\CliCommands\\' . ucfirst($commandName);

		if (!class_exists($classFQN))
		{
			throw new \RuntimeException(sprintf('Unknown Akeeba Backup CLI command class ‘%s’.', $commandName));
		}

		$classParents = class_parents($classFQN);

		if (!in_array(AbstractCommand::class, $classParents))
		{
			throw new \RuntimeException(sprintf('Invalid Akeeba Backup CLI command object ‘%s’.', $commandName));
		}

		$o = new $classFQN;

		if (method_exists($classFQN, 'setMVCFactory'))
		{
			$o->setMVCFactory($this->getMVCFactory());
		}

		if ($o instanceof DatabaseAwareInterface)
		{
			$o->setDatabase($this->getDatabase());
		}

		if (method_exists($o, 'setApplication'))
		{
			$o->setApplication($this->getApplication());
		}

		return $o;
	}

	private function getApplication(): ApplicationInterface
	{
		return $this->app;
	}
}