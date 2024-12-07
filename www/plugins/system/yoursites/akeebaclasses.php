<?php

/**
 * @version    CVS: 1.19.1RC
 * @package    com_yoursites
 * @author     Geraint Edwards <via website>
 * @copyright  2016-YOURSITES_COPYRIGHT GWE Systems Ltd
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */


defined('_JEXEC') || die();

use Akeeba\Component\AkeebaBackup\Administrator\Dispatcher\Mixin\AkeebaEngineAware;
use Akeeba\Engine\Factory;
use Joomla\CMS\Form\Field\TextField;

class YstsAkeeba
{
	use AkeebaEngineAware;

	public  function decryptSettings($value)
	{
		return $this->conditionalDecrypt($value);
	}

	private  function conditionalDecrypt($value)
	{
		// If the Factory is not already loaded we have to load the
		if (!class_exists('Akeeba\Engine\Factory'))
		{

			try
			{
				$this->loadAkeebaEngine();
				$this->loadAkeebaEngineConfiguration();
			}
			catch (Exception $e)
			{
				return $value;
			}
		}

		$secureSettings = Factory::getSecureSettings();

		return $secureSettings->decryptSettings($value);
	}
}
