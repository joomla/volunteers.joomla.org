<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Plugin\Console\AkeebaBackup\Command;

defined('_JEXEC') || die;

use Joomla\Console\Command\AbstractCommand;

interface CommandFactoryInterface
{
	public function getCLICommand(string $commandName): AbstractCommand;
}