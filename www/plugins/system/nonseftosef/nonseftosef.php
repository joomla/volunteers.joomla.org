<?php
/**
 * @Copyright
 * @package     NSTS - Non-SEF to SEF for Joomla! 3.x
 * @author      Viktor Vogel <admin@kubik-rubik.de>
 * @version     3.1.2 - 2016-06-30
 * @link        https://joomla-extensions.kubik-rubik.de/nsts-non-sef-to-sef
 *
 * @license     GNU/GPL
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
defined('_JEXEC') or die('Restricted access');

class PlgSystemNonseftosef extends JPlugin
{
	protected $app;

	public function __construct(&$subject, $config)
	{
		// Redirection in the backend would be a stupid idea...
		$this->app = JFactory::getApplication();

		if($this->app->isAdmin())
		{
			return;
		}

		parent::__construct($subject, $config);
	}

	/**
	 * Calls the plugin in the trigger onAfterRoute to avoid problems with external SEF extensions
	 *
	 * @throws Exception
	 */
	public function onAfterDispatch()
	{
		// Execute the plugin only if SEF is activated
		if(JFactory::getConfig()->get('sef') == 1)
		{
			// Only manipulate HTML document requests to avoid errors in other document types such as JSON or XML
			if(JFactory::getDocument() instanceof JDocumentHtml)
			{
				$juri = JUri::getInstance('SERVER');
				$query_parameters = $juri->getQuery(true);

				if(array_key_exists('option', $query_parameters))
				{
					// Is the option request variable a component call (as it is usually)?
					if(strtolower(substr($query_parameters['option'], 0, 4) == 'com_'))
					{
						// Should some components be excluded of being redirected?
						$exclude_components = array_filter(array_map('trim', explode("\n", $this->params->get('exclude_components'))));

						if(!empty($exclude_components))
						{
							// If we have a hit, then stop the execution of the plugin
							if(in_array($query_parameters['option'], $exclude_components))
							{
								return;
							}
						}

						// Redirect only menu items all other calls throw a 404 Not Found error
						if($this->params->get('only_menu_items'))
						{
							$item_id = false;

							if(!empty($query_parameters['Itemid']))
							{
								// Check for a valid menu entry
								$item_id = $this->checkItemId($query_parameters['Itemid']);
							}

							// Itemid in parameter but not a valid menu entry - 404
							if(empty($item_id))
							{
								$this->loadLanguage('plg_system_nonseftosef', JPATH_ADMINISTRATOR);
								throw new Exception(JText::_('PLG_NONSEFTOSEF_ERROR_404'), 404);
							}
						}

						$redirect_url = $juri->getQuery();

						// Itemid is not set in the request, try to load it with the transmitted parameters
						if(empty($query_parameters['Itemid']))
						{
							if($item_id = $this->getItemId($query_parameters))
							{
								$redirect_url = 'Itemid='.$item_id;
							}

							// Redirect request (without the Itemid in the request)
							$this->redirect($redirect_url);
						}

						// Check the transmitted Itemid for validity and if valid, use it for the redirection
						if($item_id = $this->checkItemId($query_parameters['Itemid']))
						{
							$redirect_url = 'Itemid='.$item_id;
						}

						// Redirect links with (with the Itemid in the request)
						$this->redirect($redirect_url);
					}
				}
			}
		}
	}

	/**
	 * Checks the transmitted Item ID for validity
	 *
	 * @param string $item_id
	 *
	 * @return int Item ID if valid
	 */
	private function checkItemId($item_id)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('#__menu');
		$query->where($db->quoteName('id').' = '.$db->quote($item_id));
		$query->where($db->quoteName('published').' = 1');
		$db->setQuery($query);

		return (int)$db->loadResult();
	}

	/**
	 * Gets the Item ID of the URL - the Item ID is the ID from the menu entry
	 *
	 * @param array $query_parameters
	 *
	 * @return int The Item ID of the menu entry of the URL
	 */
	private function getItemId($query_parameters)
	{
		$query_url = 'index.php?'.$this->createMenuLink($query_parameters);

		// Load the ID from the db to get sure that we have a correct ItemID
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('id');
		$query->from('#__menu');
		$query->where($db->quoteName('link').' = '.$db->quote($query_url));
		$query->where($db->quoteName('published').' = 1');
		$db->setQuery($query);

		return (int)$db->loadResult();
	}

	/**
	 * Creates a menu link in the correct form from the transmitted parameters
	 *
	 * @param array $query_parameters
	 *
	 * @return string URL-encoded query string
	 */
	private function createMenuLink($query_parameters)
	{
		$blacklist_parameters = array('catid', 'lang');

		foreach($query_parameters as $key => &$value)
		{
			if(in_array($key, $blacklist_parameters))
			{
				unset($query_parameters[$key]);

				continue;
			}

			if(strpos($value, ':') !== false)
			{
				$slug = explode(':', $value, 2);
				$value = $slug[0];
			}
		}

		return urldecode(http_build_query($query_parameters, '', '&'));
	}

	/**
	 * Redirects the request with the HTTP status code 301
	 *
	 * @param string $url
	 */
	private function redirect($url)
	{
		$this->app->redirect(JRoute::_('index.php?'.$url, 301));
	}
}
