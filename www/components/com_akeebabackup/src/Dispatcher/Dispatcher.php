<?php
/**
 * @package   akeebabackup
 * @copyright Copyright 2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\AkeebaBackup\Site\Dispatcher;

defined('_JEXEC') || die;

use Akeeba\Component\AkeebaBackup\Administrator\Dispatcher\Dispatcher as BackendDispatcher;
use Exception;
use Joomla\CMS\Document\FactoryInterface;
use Joomla\CMS\Document\JsonDocument as JDocumentJSON;
use Joomla\CMS\Factory as JFactory;

class Dispatcher extends BackendDispatcher
{
	protected $defaultController = 'backup';

	protected function onAfterDispatch()
	{
		$view = $this->input->getCmd('view', $this->defaultController);

		if (ucfirst(strtolower($view)) === 'Api')
		{
			$this->fixJsonApiOutput();
		}
	}

	/**
	 * Make sure the JSON API always outputs a JSON document.
	 *
	 * This works even when you have enabled caching, Joomla's off-line mode or tried to use tmpl=component.
	 *
	 * @throws  Exception
	 */
	private function fixJsonApiOutput()
	{
		$format = $this->input->getCmd('format', 'html');

		if ($format == 'json')
		{
			return;
		}

		$app = JFactory::getApplication();

		// Disable caching, disable offline, force use of index.php
		$app->set('caching', 0);
		$app->set('offline', 0);
		$app->set('themeFile', 'index.php');

		/** @var FactoryInterface $documentFactory */
		$documentFactory = JFactory::getContainer()->get(FactoryInterface::class);
		/** @var JDocumentJSON $doc */
		$doc = $documentFactory->createDocument('json');

		$app->loadDocument($doc);

		if (property_exists(JFactory::class, 'document'))
		{
			/** @noinspection PhpDeprecationInspection */
			JFactory::$document = $doc;
		}

		// Set a custom document name
		/** @var JDocumentJSON $document */
		$document = $this->app->getDocument();
		$document->setName('akeeba_backup');
	}

	/**
	 * Load the language.
	 *
	 * Automatically loads en-GB and the site's fallback language (if different), then merges it with the language of
	 * the current user. First tries loading languages from the site's main folders before falling back to the ones
	 * shipped with the component itself.
	 *
	 * @return  void
	 *
	 * @since   9.0.0
	 */
	protected function loadLanguage()
	{
		$jLang = $this->app->getLanguage();

		$jLang->load($this->option, JPATH_SITE, 'en-GB', true, true) ||
		$jLang->load($this->option, JPATH_SITE . '/components/com_akeebabackup', 'en-GB', true, true);

		$jLang->load($this->option, JPATH_ADMINISTRATOR, 'en-GB', true, true) ||
		$jLang->load($this->option, JPATH_SITE . '/components/com_akeebabackup', 'en-GB', true, true);

		$jLang->load($this->option, JPATH_BASE, null, true) ||
		$jLang->load($this->option, JPATH_SITE . '/components/com_akeebabackup', null, true);

		$jLang->load($this->option, JPATH_ADMINISTRATOR, null, true) ||
		$jLang->load($this->option, JPATH_ADMINISTRATOR . '/components/com_akeebabackup', null, true);
	}

}