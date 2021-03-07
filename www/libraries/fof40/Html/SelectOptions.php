<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Html;

defined('_JEXEC') || die;

use Joomla\CMS\Cache\Cache;
use Joomla\CMS\Factory as JoomlaFactory;
use Joomla\CMS\Helper\UserGroupsHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use stdClass;

/**
 * Returns arrays of HTMLHelper select options for Joomla-specific information such as access levels.
 *
 * @method static array access() access(array $params)
 * @method static array usergroups() usergroups(array $params)
 * @method static array cachehandlers() cachehandlers(array $params)
 * @method static array components() components(array $params)
 * @method static array languages() languages(array $params)
 * @method static array published() published(array $params)
 */
class SelectOptions
{
	private static $cache = [];

	/**
	 * Magic method to handle static calls
	 *
	 * @param   string  $name       The name of the static method being called
	 * @param   array   $arguments  Optional arguments, if they are supported by the options type.
	 *
	 * @return  mixed
	 * @since   3.3.0
	 */
	public static function __callStatic(string $name, array $arguments = [])
	{
		return self::getOptions($name, $arguments);
	}

	/**
	 * Get a list of Joomla options of the type you specify. Supported types
	 * - access         View access levels
	 * - usergroups     User groups
	 * - cachehandlers  Cache handlers
	 * - components     Installed components accessible by the current user
	 * - languages      Site or administrator languages
	 * - published      Published status
	 *
	 * Global params:
	 * - cache  Should I returned cached data? Default: true.
	 *
	 * See the private static methods of this class for more information on params.
	 *
	 * @param   string  $type    The options type to get
	 * @param   array   $params  Optional arguments, if they are supported by the options type.
	 *
	 * @return  stdClass[]
	 * @since   3.3.0
	 * @api
	 */
	public static function getOptions(string $type, array $params = []): array
	{
		if ((substr($type, 0, 1) == '_') || !method_exists(__CLASS__, '_api_' . $type))
		{
			throw new \InvalidArgumentException(__CLASS__ . "does not support option type '$type'.");
		}

		$useCache = true;

		if (isset($params['cache']))
		{
			$useCache = isset($params['cache']);
			unset($params['cache']);
		}

		$cacheKey = sha1($type . '--' . print_r($params, true));
		$fetchNew = !$useCache || ($useCache && !isset(self::$cache[$cacheKey]));
		$ret      = [];

		if ($fetchNew)
		{
			$ret = forward_static_call_array([__CLASS__, '_api_' . $type], [$params]);
		}

		if (!$useCache)
		{
			return $ret;
		}

		if ($fetchNew)
		{
			self::$cache[$cacheKey] = $ret;
		}

		return self::$cache[$cacheKey];
	}

	/**
	 * Joomla! Access Levels (previously: view access levels)
	 *
	 * Available params:
	 * - allLevels: Show an option for all levels (default: false)
	 *
	 * @param   array  $params  Parameters
	 *
	 * @return  stdClass[]
	 * @since   3.3.0
	 * @internal
	 *
	 * @see     \Joomla\CMS\HTML\Helpers\Access::level()
	 *
	 * @noinspection PhpUnusedPrivateMethodInspection
	 */
	private static function _api_access(array $params = []): array
	{
		$db    = JoomlaFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName('a.id', 'value') . ', ' . $db->quoteName('a.title', 'text'))
			->from($db->quoteName('#__viewlevels', 'a'))
			->group($db->quoteName(['a.id', 'a.title', 'a.ordering']))
			->order($db->quoteName('a.ordering') . ' ASC')
			->order($db->quoteName('title') . ' ASC');

		// Get the options.
		$db->setQuery($query);
		$options = $db->loadObjectList() ?? [];

		if (isset($params['allLevels']) && $params['allLevels'])
		{
			array_unshift($options, HTMLHelper::_('select.option', '', Text::_('JOPTION_ACCESS_SHOW_ALL_LEVELS')));
		}

		return $options;
	}

	/**
	 * Joomla! User Groups
	 *
	 * Available params:
	 * - allGroups: Show an option for all groups (default: false)
	 *
	 * @param   array  $params  Parameters
	 *
	 * @return  stdClass[]
	 * @since   3.3.0
	 * @internal
	 *
	 * @see     \Joomla\CMS\HTML\Helpers\Access::usergroup()
	 *
	 * @noinspection PhpUnusedPrivateMethodInspection
	 */
	private static function _api_usergroups(array $params = []): array
	{
		$options = array_values(UserGroupsHelper::getInstance()->getAll());

		for ($i = 0, $n = count($options); $i < $n; $i++)
		{
			$options[$i]->value = $options[$i]->id;
			$options[$i]->text  = str_repeat('- ', $options[$i]->level) . $options[$i]->title;
		}

		// If all usergroups is allowed, push it into the array.
		if (isset($params['allGroups']) && $params['allGroups'])
		{
			array_unshift($options, HTMLHelper::_('select.option', '', Text::_('JOPTION_ACCESS_SHOW_ALL_GROUPS')));
		}

		return $options;
	}

	/**
	 * Joomla cache handlers
	 *
	 * @param   array  $params  Ignored
	 *
	 * @return  stdClass[]
	 * @since   3.3.0
	 * @internal
	 *
	 * @noinspection PhpUnusedPrivateMethodInspection
	 */
	private static function _api_cachehandlers(array $params = []): array
	{
		$options = [];

		// Convert to name => name array.
		foreach (Cache::getStores() as $store)
		{
			$options[] = HTMLHelper::_('select.option', $store, Text::_('JLIB_FORM_VALUE_CACHE_' . $store), 'value', 'text');
		}

		return $options;
	}

	/**
	 * Get a list of all installed components and also translates them.
	 *
	 * Available params:
	 * - client_ids  Array of Joomla application client IDs
	 *
	 * @param   array  $params
	 *
	 * @return  stdClass[]
	 * @since   3.3.0
	 * @internal
	 *
	 * @noinspection PhpUnusedPrivateMethodInspection
	 */
	private static function _api_components(array $params = []): array
	{
		$db = JoomlaFactory::getDbo();

		// Check for client_ids override
		$client_ids = $params['client_ids'] ?? [0, 1];

		if (is_string($client_ids))
		{
			$client_ids = explode(',', $client_ids);
		}

		// Calculate client_ids where clause
		$client_ids = array_map(function ($client_id) use ($db) {
			return $db->q((int) trim($client_id));
		}, $client_ids);

		$query      = $db->getQuery(true)
			->select(
				[
					$db->qn('name'),
					$db->qn('element'),
					$db->qn('client_id'),
					$db->qn('manifest_cache'),
				]
			)
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->q('component'))
			->where($db->qn('client_id') . ' IN (' . implode(',', $client_ids) . ')');
		$components = $db->setQuery($query)->loadObjectList('element');

		// Convert to array of objects, so we can use sortObjects()
		// Also translate component names with Text::_()
		$aComponents = [];
		$user        = JoomlaFactory::getUser();

		foreach ($components as $component)
		{
			// Don't show components in the list where the user doesn't have access for
			// TODO: perhaps add an option for this
			if (!$user->authorise('core.manage', $component->element))
			{
				continue;
			}

			$aComponents[$component->element] = (object) [
				'value' => $component->element,
				'text'  => self::_translateComponentName($component),
			];
		}

		// Reorder the components array, because the alphabetical
		// ordering changed due to the Text::_() translation
		uasort(
			$aComponents,
			function ($a, $b) {
				return strcasecmp($a->text, $b->text);
			}
		);

		return $aComponents;
	}

	/**
	 * Method to get the field options.
	 *
	 * Available params:
	 * - client  'site' (default) or 'administrator'
	 * - none    Text to show for "all languages" option, use empty string to remove it
	 *
	 * @param   array  $params
	 *
	 * @return  object[]  Languages for the specified client
	 * @since   3.3.0
	 * @internal
	 *
	 * @noinspection PhpUnusedPrivateMethodInspection
	 */
	private static function _api_languages(array $params): array
	{
		$client = $params['client'] ?? 'site';

		if (!in_array($client, ['site', 'administrator']))
		{
			$client = 'site';
		}

		// Make sure the languages are sorted base on locale instead of random sorting
		$options = LanguageHelper::createLanguageList(null, constant('JPATH_' . strtoupper($client)), true, true);

		if (count($options) > 1)
		{
			usort(
				$options,
				function ($a, $b) {
					return strcmp($a['value'], $b['value']);
				}
			);
		}

		$none = $params['none'] ?? '*';

		if (!empty($none))
		{
			array_unshift($options, HTMLHelper::_('select.option', '*', Text::_($none)));
		}

		return $options;
	}

	/**
	 * Options for a Published field
	 *
	 * Params:
	 * - none           Placeholder for no selection (empty key). Default: null.
	 * - published      Show "Published"? Default: true
	 * - unpublished    Show "Unpublished"? Default: true
	 * - archived       Show "Archived"? Default: false
	 * - trash          Show "Trashed"? Default: false
	 * - all            Show "All" option? This is different than none, the key is '*'. Default: false
	 *
	 * @param   array  $params
	 *
	 * @return  array
	 * @since   3.3.0
	 * @internal
	 *
	 * @noinspection PhpUnusedPrivateMethodInspection
	 */
	private static function _api_published(array $params = []): array
	{
		$config = array_merge([
			'none'        => '',
			'published'   => true,
			'unpublished' => true,
			'archived'    => false,
			'trash'       => false,
			'all'         => false,
		], $params);

		$options = [];

		if (!empty($config['none']))
		{
			$options[] = HTMLHelper::_('select.option', '', Text::_($config['none']));
		}

		if ($config['published'])
		{
			$options[] = HTMLHelper::_('select.option', '1', Text::_('JPUBLISHED'));
		}

		if ($config['unpublished'])
		{
			$options[] = HTMLHelper::_('select.option', '0', Text::_('JUNPUBLISHED'));
		}

		if ($config['archived'])
		{
			$options[] = HTMLHelper::_('select.option', '2', Text::_('JARCHIVED'));
		}

		if ($config['trash'])
		{
			$options[] = HTMLHelper::_('select.option', '-2', Text::_('JTRASHED'));
		}

		if ($config['all'])
		{
			$options[] = HTMLHelper::_('select.option', '*', Text::_('JALL'));
		}

		return $options;
	}

	/**
	 * Options for a Published field
	 *
	 * Params:
	 * - none           Placeholder for no selection (empty key). Default: null.
	 *
	 * @param   array  $params
	 *
	 * @return  array
	 * @since   3.3.0
	 * @internal
	 *
	 * @noinspection PhpUnusedPrivateMethodInspection
	 */
	private static function _api_boolean(array $params = []): array
	{
		$config = array_merge([
			'none' => '',
		], $params);

		$options = [];

		if (!empty($config['none']))
		{
			$options[] = HTMLHelper::_('select.option', '', Text::_($config['none']));
		}

		$options[] = HTMLHelper::_('select.option', '1', Text::_('JYES'));
		$options[] = HTMLHelper::_('select.option', '0', Text::_('JNO'));

		return $options;
	}


	/**
	 * Translate a component name
	 *
	 * @param   object  $item  The component object
	 *
	 * @return  string  $text  The translated name of the extension
	 * @since   3.3.0
	 * @internal
	 *
	 * @see     /administrator/com_installer/models/extension.php
	 */
	private static function _translateComponentName(object $item): string
	{
		// Map the manifest cache to $item. This is needed to get the name from the
		// manifest_cache and NOT from the name column, else some Text::_() translations fails.
		$mData = json_decode($item->manifest_cache);

		if ($mData)
		{
			foreach ($mData as $key => $value)
			{
				if ($key == 'type')
				{
					// Ignore the type field
					continue;
				}

				$item->$key = $value;
			}
		}

		$lang   = JoomlaFactory::getLanguage();
		$source = JPATH_ADMINISTRATOR . '/components/' . $item->element;
		$lang->load("$item->element.sys", JPATH_ADMINISTRATOR, null, false, false)
		|| $lang->load("$item->element.sys", $source, null, false, false)
		|| $lang->load("$item->element.sys", JPATH_ADMINISTRATOR, $lang->getDefault(), false, false)
		|| $lang->load("$item->element.sys", $source, $lang->getDefault(), false, false);

		return Text::_($item->name);
	}
}
