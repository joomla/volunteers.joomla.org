<?php
/**
 * @package   akeebabackup
 * @copyright Copyright (c)2006-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Alice\Check\Requirements;

use Akeeba\Alice\Check\Base;
use Akeeba\Engine\Factory;
use Exception;
use Joomla\CMS\Language\Text as JText;

/**
 * Checks for database permissions (SHOW permissions)
 */
class DatabasePermissions extends Base
{
	public function __construct($logFile = null)
	{
		$this->priority         = 40;
		$this->checkLanguageKey = 'COM_AKEEBA_ALICE_ANALYZE_REQUIREMENTS_DBPERMISSIONS';

		parent::__construct($logFile);
	}

	public function check()
	{
		$db = Factory::getDatabase();

		// Can I execute SHOW statements?
		try
		{
			$result = $db->setQuery('SHOW TABLES')->query();
		}
		catch (Exception $e)
		{
			$result = false;
		}

		if (!$result)
		{
			$this->setResult(-1);
			$this->setErrorLanguageKey([
				'COM_AKEEBA_ALICE_ANALYZE_REQUIREMENTS_DBPERMISSIONS_ERROR',
			]);

			return;
		}

		try
		{
			$result = $db->setQuery('SHOW CREATE TABLE ' . $db->nameQuote('#__ak_profiles'))->query();
		}
		catch (Exception $e)
		{
			$result = false;
		}

		if (!$result)
		{
			$this->setResult(-1);
			$this->setErrorLanguageKey([
				'COM_AKEEBA_ALICE_ANALYZE_REQUIREMENTS_DBPERMISSIONS_ERROR',
			]);

			return;
		}
	}

	public function getSolution()
	{
		return JText::_('COM_AKEEBA_ALICE_ANALYZE_REQUIREMENTS_DBPERMISSIONS_SOLUTION');
	}
}
