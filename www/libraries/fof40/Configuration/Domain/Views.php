<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\Configuration\Domain;

use SimpleXMLElement;

defined('_JEXEC') || die;

/**
 * Configuration parser for the view-specific settings
 *
 * @since    2.1
 */
class Views implements DomainInterface
{
	/**
	 * Parse the XML data, adding them to the $ret array
	 *
	 * @param   SimpleXMLElement   $xml  The XML data of the component's configuration area
	 * @param   array             &$ret  The parsed data, in the form of a hash array
	 *
	 * @return  void
	 */
	public function parseDomain(SimpleXMLElement $xml, array &$ret): void
	{
		// Initialise
		$ret['views'] = [];

		// Parse view configuration
		$viewData = $xml->xpath('view');

		// Sanity check

		if (empty($viewData))
		{
			return;
		}

		foreach ($viewData as $aView)
		{
			$key = (string) $aView['name'];

			// Parse ACL options
			$ret['views'][$key]['acl'] = [];
			$aclData                   = $aView->xpath('acl/task');

			foreach ($aclData as $acl)
			{
				$k                             = (string) $acl['name'];
				$ret['views'][$key]['acl'][$k] = (string) $acl;
			}

			// Parse taskmap
			$ret['views'][$key]['taskmap'] = [];
			$taskmapData                   = $aView->xpath('taskmap/task');

			foreach ($taskmapData as $map)
			{
				$k                                 = (string) $map['name'];
				$ret['views'][$key]['taskmap'][$k] = (string) $map;
			}

			// Parse controller configuration
			$ret['views'][$key]['config'] = [];
			$optionData                   = $aView->xpath('config/option');

			foreach ($optionData as $option)
			{
				$k                                = (string) $option['name'];
				$ret['views'][$key]['config'][$k] = (string) $option;
			}

			// Parse the toolbar
			$ret['views'][$key]['toolbar'] = [];
			$toolBars                      = $aView->xpath('toolbar');

			foreach ($toolBars as $toolBar)
			{
				$taskName = isset($toolBar['task']) ? (string) $toolBar['task'] : '*';

				// If a toolbar title is specified, create a title element.
				if (isset($toolBar['title']))
				{
					$ret['views'][$key]['toolbar'][$taskName]['title'] = [
						'value' => (string) $toolBar['title'],
					];
				}

				// Parse the toolbar buttons data
				$toolbarData = $toolBar->xpath('button');

				foreach ($toolbarData as $button)
				{
					$k                                                     = (string) $button['type'];
					$ret['views'][$key]['toolbar'][$taskName][$k]          = current($button->attributes());
					$ret['views'][$key]['toolbar'][$taskName][$k]['value'] = (string) $button;
				}
			}
		}
	}

	/**
	 * Return a configuration variable
	 *
	 * @param   string  &$configuration  Configuration variables (hashed array)
	 * @param   string   $var            The variable we want to fetch
	 * @param   mixed    $default        Default value
	 *
	 * @return  mixed  The variable's value
	 */
	public function get(array &$configuration, string $var, $default = null)
	{
		$parts = explode('.', $var);

		$view   = $parts[0];
		$method = 'get' . ucfirst($parts[1]);

		if (!method_exists($this, $method))
		{
			return $default;
		}

		array_shift($parts);
		array_shift($parts);

		return $this->$method($view, $configuration, $parts, $default);
	}

	/**
	 * Internal function to return the task map for a view
	 *
	 * @param   string   $view           The view for which we will be fetching a task map
	 * @param   array   &$configuration  The configuration parameters hash array
	 * @param   array    $params         Extra options (not used)
	 * @param   array    $default        ßDefault task map; empty array if not provided
	 *
	 * @return  array  The task map as a hash array in the format task => method
	 */
	protected function getTaskmap(string $view, array &$configuration, array $params = [], ?array $default = []): ?array
	{
		$taskmap = [];

		if (isset($configuration['views']['*']) && isset($configuration['views']['*']['taskmap']))
		{
			$taskmap = $configuration['views']['*']['taskmap'];
		}

		if (isset($configuration['views'][$view]) && isset($configuration['views'][$view]['taskmap']))
		{
			$taskmap = array_merge($taskmap, $configuration['views'][$view]['taskmap']);
		}

		if (empty($taskmap))
		{
			return $default;
		}

		return $taskmap;
	}

	/**
	 * Internal method to return the ACL mapping (privilege required to access
	 * a specific task) for the given view's tasks
	 *
	 * @param   string   $view           The view for which we will be fetching a task map
	 * @param   array   &$configuration  The configuration parameters hash array
	 * @param   array    $params         Extra options; key 0 defines the task we want to fetch
	 * @param   string   $default        Default ACL option; empty (no ACL check) if not defined
	 *
	 * @return  string|array  The privilege required to access this view
	 */
	protected function getAcl(string $view, array &$configuration, array $params = [], ?string $default = '')
	{
		$aclmap = [];

		if (isset($configuration['views']['*']) && isset($configuration['views']['*']['acl']))
		{
			$aclmap = $configuration['views']['*']['acl'];
		}

		if (isset($configuration['views'][$view]) && isset($configuration['views'][$view]['acl']))
		{
			$aclmap = array_merge($aclmap, $configuration['views'][$view]['acl']);
		}

		$acl = $default;

		if (empty($params) || empty($params[0]))
		{
			return $aclmap;
		}

		if (isset($aclmap['*']))
		{
			$acl = $aclmap['*'];
		}

		if (isset($aclmap[$params[0]]))
		{
			$acl = $aclmap[$params[0]];
		}

		return $acl;
	}

	/**
	 * Internal method to return the a configuration option for the view. These
	 * are equivalent to $config array options passed to the Controller
	 *
	 * @param   string             $view           The view for which we will be fetching a task map
	 * @param   array   &          $configuration  The configuration parameters hash array
	 * @param   array              $params         Extra options; key 0 defines the option variable we want to fetch
	 * @param   string|array|null  $default        Default option; null if not defined
	 *
	 * @return  string|array|null  The setting for the requested option
	 */
	protected function getConfig(string $view, array &$configuration, array $params = [], $default = null)
	{
		$ret = $default;

		$config = [];

		if (isset($configuration['views']['*']['config']))
		{
			$config = $configuration['views']['*']['config'];
		}

		if (isset($configuration['views'][$view]['config']))
		{
			$config = array_merge($config, $configuration['views'][$view]['config']);
		}

		if (empty($params) || empty($params[0]))
		{
			return $config;
		}

		if (isset($config[$params[0]]))
		{
			$ret = $config[$params[0]];
		}

		return $ret;
	}

	/**
	 * Internal method to return the toolbar infos.
	 *
	 * @param   string      $view           The view for which we will be fetching buttons
	 * @param   array   &   $configuration  The configuration parameters hash array
	 * @param   array       $params         Extra options
	 * @param   array|null  $default        Default option
	 *
	 * @return  array|null  The toolbar data for this view
	 */
	protected function getToolbar(string $view, array &$configuration, array $params = [], ?array $default = []): ?array
	{
		$toolbar = [];

		if (isset($configuration['views']['*'])
			&& isset($configuration['views']['*']['toolbar'])
			&& isset($configuration['views']['*']['toolbar']['*']))
		{
			$toolbar = $configuration['views']['*']['toolbar']['*'];
		}

		if (isset($configuration['views']['*'])
			&& isset($configuration['views']['*']['toolbar'])
			&& isset($configuration['views']['*']['toolbar'][$params[0]]))
		{
			$toolbar = array_merge($toolbar, $configuration['views']['*']['toolbar'][$params[0]]);
		}

		if (isset($configuration['views'][$view])
			&& isset($configuration['views'][$view]['toolbar'])
			&& isset($configuration['views'][$view]['toolbar']['*']))
		{
			$toolbar = array_merge($toolbar, $configuration['views'][$view]['toolbar']['*']);
		}

		if (isset($configuration['views'][$view])
			&& isset($configuration['views'][$view]['toolbar'])
			&& isset($configuration['views'][$view]['toolbar'][$params[0]]))
		{
			$toolbar = array_merge($toolbar, $configuration['views'][$view]['toolbar'][$params[0]]);
		}

		if (empty($toolbar))
		{
			return $default;
		}

		return $toolbar;
	}
}
