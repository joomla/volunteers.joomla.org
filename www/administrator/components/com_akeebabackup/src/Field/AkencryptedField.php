<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Field;

defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\AkeebaEngineTrait;
use Akeeba\Engine\Factory;
use Exception;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Form\Field\TextField;
use Joomla\Database\DatabaseInterface;

class AkencryptedField extends TextField
{
	use AkeebaEngineTrait;

	protected $type = "Akencrypted";

	protected function getInput()
	{
		$this->value = $this->conditionalDecrypt($this->value);

		return parent::getInput();
	}

	private function conditionalDecrypt($value)
	{
		// If the Factory is not already loaded we have to load the
		if (!class_exists('Akeeba\Engine\Factory'))
		{
			$dbo = method_exists($this, 'getDatabase')
				? $this->getDatabase()
				: JoomlaFactory::getApplication()->bootComponent('com_akeebabackup')->getContainer()->get(DatabaseInterface::class);

			try
			{
				$this->loadAkeebaEngine($dbo);
				$this->loadAkeebaEngineConfiguration();
			}
			catch (Exception $e)
			{
				return $value;
			}
		}

		$secureSettings = Factory::getSecureSettings();

		return $secureSettings->decryptSettings($this->value);
	}
}
