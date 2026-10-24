<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Mixin;

defined('_JEXEC') or die;

use Joomla\CMS\Toolbar\Toolbar;

trait ViewToolbarTrait
{
	protected function getToolbarCompat(): Toolbar
	{
		$document = $this->getDocument();

		// Joomla 5 and later
		if (method_exists($document, 'getToolbar'))
		{
			return $document->getToolbar();
		}

		// Joomla 4.x
		/** @noinspection PhpDeprecationInspection */
		return Toolbar::getInstance();
	}
}