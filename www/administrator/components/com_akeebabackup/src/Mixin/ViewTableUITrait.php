<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Mixin;

defined('_JEXEC') || die;

trait ViewTableUITrait
{
	public function tableColumnsAutohide(): void
	{
		try
		{
			$this->getDocument()->getWebAssetManager()->useScript('table.columns');
		}
		catch (\Throwable $e)
		{
			// This might indeed fail on old Joomla! versions.
		}
	}

	public function tableColumnsMultiselect(?string $tableSelector = null): void
	{
		try
		{
			$this->getDocument()->getWebAssetManager()->useScript('multiselect');

			if (empty($tableSelector))
			{
				return;
			}

			$this->getDocument()->addScriptOptions('js-multiselect', [
				'formName' => $tableSelector
			]);
		}
		catch (\Throwable $e)
		{
			// This might indeed fail on old Joomla! versions.
		}
	}
}