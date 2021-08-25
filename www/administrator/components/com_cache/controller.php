<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_cache
 *
 * @copyright   (C) 2008 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Cache Controller
 *
 * @since  1.6
 */
class CacheController extends JControllerLegacy
{
	/**
	 * Display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController  This object to support chaining.
	 *
	 * @since   1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		JLoader::register('CacheHelper', JPATH_ADMINISTRATOR . '/components/com_cache/helpers/cache.php');

		// Get the document object.
		$document = JFactory::getDocument();

		// Set the default view name and format from the Request.
		$vName   = $this->input->get('view', 'cache');
		$vFormat = $document->getType();
		$lName   = $this->input->get('layout', 'default', 'string');

		// Get and render the view.
		if ($view = $this->getView($vName, $vFormat))
		{
			switch ($vName)
			{
				case 'purge':
					break;
				case 'cache':
				default:
					$model = $this->getModel($vName);
					$view->setModel($model, true);
					break;
			}

			$view->setLayout($lName);

			// Push document object into the view.
			$view->document = $document;

			// Load the submenu.
			CacheHelper::addSubmenu($this->input->get('view', 'cache'));

			$view->display();
		}
	}

	/**
	 * Method to delete a list of cache groups.
	 *
	 * @return  void
	 */
	public function delete()
	{
		// Check for request forgeries
		$this->checkToken();

		$cid = $this->input->post->get('cid', array(), 'array');

		if (empty($cid))
		{
			JFactory::getApplication()->enqueueMessage(JText::_('JERROR_NO_ITEMS_SELECTED'), 'warning');
		}
		else
		{
			$result = $this->getModel('cache')->cleanlist($cid);

			if ($result !== array())
			{
				JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_CACHE_EXPIRED_ITEMS_DELETE_ERROR', implode(', ', $result)), 'error');
			}
			else
			{
				JFactory::getApplication()->enqueueMessage(JText::_('COM_CACHE_EXPIRED_ITEMS_HAVE_BEEN_DELETED'), 'message');
			}
		}

		$this->setRedirect('index.php?option=com_cache');
	}

	/**
	 * Method to delete all cache groups.
	 *
	 * @return  void
	 *
	 * @since  3.6.0
	 */
	public function deleteAll()
	{
		// Check for request forgeries
		$this->checkToken();

		$app        = JFactory::getApplication();
		$model      = $this->getModel('cache');
		$allCleared = true;
		$clients    = array(1, 0);

		foreach ($clients as $client)
		{
			$mCache    = $model->getCache($client);
			$clientStr = JText::_($client ? 'JADMINISTRATOR' : 'JSITE') .' > ';

			foreach ($mCache->getAll() as $cache)
			{
				if ($mCache->clean($cache->group) === false)
				{
					$app->enqueueMessage(JText::sprintf('COM_CACHE_EXPIRED_ITEMS_DELETE_ERROR', $clientStr . $cache->group), 'error');
					$allCleared = false;
				}
			}
		}

		if ($allCleared)
		{
			$app->enqueueMessage(JText::_('COM_CACHE_MSG_ALL_CACHE_GROUPS_CLEARED'), 'message');
		}
		else
		{
			$app->enqueueMessage(JText::_('COM_CACHE_MSG_SOME_CACHE_GROUPS_CLEARED'), 'warning');
		}

		$app->triggerEvent('onAfterPurge', array());
		$this->setRedirect('index.php?option=com_cache&view=cache');
	}

	/**
	 * Purge the cache.
	 *
	 * @return  void
	 */
	public function purge()
	{
		// Check for request forgeries
		$this->checkToken();

		if (!$this->getModel('cache')->purge())
		{
			JFactory::getApplication()->enqueueMessage(JText::_('COM_CACHE_EXPIRED_ITEMS_PURGING_ERROR'), 'error');
		}
		else
		{
			JFactory::getApplication()->enqueueMessage(JText::_('COM_CACHE_EXPIRED_ITEMS_HAVE_BEEN_PURGED'), 'message');
		}

		$this->setRedirect('index.php?option=com_cache&view=purge');
	}
}
