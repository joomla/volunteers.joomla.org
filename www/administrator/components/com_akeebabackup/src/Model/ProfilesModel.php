<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Administrator\Model;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Mixin\ModelStateFixTrait;
use Akeeba\Component\AkeebaBackup\Administrator\Table\ProfileTable;
use Akeeba\Engine\Platform;
use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use RuntimeException;

#[\AllowDynamicProperties]
class ProfilesModel extends ListModel
{
	use ModelStateFixTrait;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @throws  Exception
	 * @since   9.0.0
	 *
	 */
	public function __construct($config = [], ?MVCFactoryInterface $factory = null)
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = [
				'search',
				'quickicon',
			];
		}

		parent::__construct($config, $factory);
	}

	/**
	 * Returns an associative array with profile IDs as keys and the post-processing engine as values
	 *
	 * @return  array
	 */
	public function getPostProcessingEnginePerProfile()
	{
		// Cache the current profile's ID
		$currentProfileID = JoomlaFactory::getApplication()->getSession()->get('akeebabackup.profile', null);

		// Get the IDs of all profiles
		$db    = $this->getDatabase();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select($db->qn('id'))
			->from($db->qn('#__akeebabackup_profiles'));
		$db->setQuery($query);
		$profiles = $db->loadColumn();

		// Initialise return;
		$engines = [];

		// Loop all profiles
		foreach ($profiles as $profileId)
		{
			Platform::getInstance()->load_configuration($profileId);
			$profileConfiguration = \Akeeba\Engine\Factory::getConfiguration();
			$engines[$profileId]  = $profileConfiguration->get('akeeba.advanced.postproc_engine');
		}

		// Reload the current profile
		Platform::getInstance()->load_configuration($currentProfileID);

		return $engines;
	}

	/**
	 * Save a profile from imported configuration data. The $data array must contain the keys description (profile
	 * description), configuration (engine configuration INI data) and filters (inclusion and inclusion filters JSON
	 * configuration data).
	 *
	 * @param   array  $data  See above
	 *
	 * @return  int|null
	 *
	 * @throws  RuntimeException|Exception  When an import error occurs
	 */
	public function import($data): ?int
	{
		// Check for data validity
		$isValid =
			is_array($data) &&
			!empty($data) &&
			array_key_exists('description', $data) &&
			array_key_exists('configuration', $data) &&
			array_key_exists('filters', $data);

		if (!$isValid)
		{
			throw new RuntimeException(Text::_('COM_AKEEBABACKUP_PROFILES_ERR_IMPORT_INVALID'));
		}

		// Unset the id, if it exists
		if (array_key_exists('id', $data))
		{
			unset($data['id']);
		}

		$data['akeeba.flag.confwiz'] = 1;

		// Try saving the profile
		/** @var ProfileTable $table */
		$table  = $this->getTable('Profile');
		$result = $table->save($data);

		if (!$result)
		{
			throw new RuntimeException(Text::_('COM_AKEEBABACKUP_PROFILES_ERR_IMPORT_FAILED'));
		}

		return $table->getId();
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 * @since   9.0.0
	 *
	 */
	protected function populateState($ordering = 'id', $direction = 'asc')
	{
		$app = Factory::getApplication();

		$search = $app->getUserStateFromRequest($this->context . 'filter.search', 'filter_search', '', 'string');
		$this->setState('filter.search', $search);

		$search = $app->getUserStateFromRequest($this->context . 'filter.quickicon', 'filter_quickicon', '', 'string');
		$this->setState('filter.quickicon', ($search === '') ? $search : (int) $search);

		parent::populateState($ordering, $direction);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return DatabaseQuery|QueryInterface
	 *
	 * @throws  Exception
	 * @since   9.0.0
	 */
	protected function getListQuery()
	{
		$db    = $this->getDatabase();
		$query = (method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true))
			->select(['p.*', $db->quoteName('ag.title', 'access_level')])
			->from($db->qn('#__akeebabackup_profiles', 'p'))
			->join('LEFT', $db->quoteName('#__viewlevels', 'ag'), $db->quoteName('ag.id') . ' = ' . $db->quoteName('p.access'))
		;

		// Description / ID search filter
		$search = $this->getState('filter.search');

		if (!empty($search))
		{
			if (stripos($search, 'id:') === 0)
			{
				$ids = (int) substr($search, 3);
				$query->where($db->quoteName('id') . ' = :id')
					->bind(':id', $ids, ParameterType::INTEGER);
			}
			else
			{
				$search = '%' . $search . '%';
				$query->where($db->qn('description') . ' LIKE :search')
					->bind(':search', $search);
			}
		}

		// Quickicon filter
		$quickIcon = $this->getState('filter.quickicon');

		if (is_numeric($quickIcon))
		{
			$query->where($db->qn('quickicon') . ' = :quickicon')
				->bind(':quickicon', $quickIcon, ParameterType::INTEGER);
		}

		$access_levels = $this->getState('filter.access_level');

		if (!empty($access_levels) && is_array($access_levels))
		{
			$query->whereIn($db->quoteName('p.access'), $access_levels);
		}

		return $query;
	}

}