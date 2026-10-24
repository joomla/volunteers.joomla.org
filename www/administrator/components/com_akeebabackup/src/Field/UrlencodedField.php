<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Field;

defined('_JEXEC') || die();

use Joomla\CMS\Form\Field\TextField;

class UrlencodedField extends TextField
{
	protected $type = 'Urlencoded';

	protected function getInput()
	{
		$this->value = urlencode($this->value);

		return parent::getInput();
	}
}
