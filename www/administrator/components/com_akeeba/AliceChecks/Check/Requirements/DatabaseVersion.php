<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Alice\Check\Requirements;

use Akeeba\Alice\Check\Base;
use Joomla\CMS\Factory as JFactory;
use Joomla\CMS\Language\Text as JText;

/**
 * Checks for supported DB type and version
 */
class DatabaseVersion extends Base
{
	public function __construct($logFile = null)
	{
		$this->priority         = 20;
		$this->checkLanguageKey = 'COM_AKEEBA_ALICE_ANALYZE_REQUIREMENTS_DATABASE';

		parent::__construct($logFile);
	}

	public function check()
	{
		// Instead of reading the log, I can simply take the JDatabase object and test it
		$db        = JFactory::getDbo();
		$connector = strtolower($db->name);
		$version   = $db->getVersion();

		switch ($connector)
		{
			case 'mysql':
			case 'mysqli':
			case 'pdomysql':
				if (version_compare($version, '5.0.47', 'lt'))
				{
					$this->setResult(-1);
					$this->setErrorLanguageKey([
						'COM_AKEEBA_ALICE_ANALYZE_REQUIREMENTS_DATABASE_VERSION_TOO_OLD', $version,
					]);
				}
				break;

			case 'pdo':
			case 'sqlite':
				$this->setResult(-1);
				$this->setErrorLanguageKey([
					'COM_AKEEBA_ALICE_ANALYZE_REQUIREMENTS_DATABASE_UNSUPPORTED', $connector,
				]);
				break;

			default:
				$this->setResult(-1);
				$this->setErrorLanguageKey(['COM_AKEEBA_ALICE_ANALYZE_REQUIREMENTS_DATABASE_UNKNOWN', $connector]);
				break;
		}
	}

	public function getSolution()
	{
		return JText::_('COM_AKEEBA_ALICE_ANALYZE_REQUIREMENTS_DATABASE_SOLUTION');
	}
}
