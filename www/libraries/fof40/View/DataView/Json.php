<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF40\View\DataView;

defined('_JEXEC') || die;

use FOF40\Model\DataModel;
use Joomla\CMS\Document\Document as JoomlaDocument;
use Joomla\CMS\Document\JsonDocument;
use Joomla\CMS\Uri\Uri;

class Json extends Raw implements DataViewInterface
{
	/**
	 * Set to true if your onBefore* methods have already populated the item, items, limitstart etc properties used to
	 * render a JSON document.
	 *
	 * @var bool
	 */
	public $alreadyLoaded = false;

	/**
	 * Record listing offset (how many records to skip before starting showing some)
	 *
	 * @var   int
	 */
	protected $limitStart = 0;

	/**
	 * Record listing limit (how many records to show)
	 *
	 * @var   int
	 */
	protected $limit = 10;

	/**
	 * Total number of records in the result set
	 *
	 * @var   int
	 */
	protected $total = 0;

	/**
	 * The record being displayed
	 *
	 * @var   DataModel
	 */
	protected $item;

	/**
	 * Overrides the default method to execute and display a template script.
	 * Instead of loadTemplate is uses loadAnyTemplate.
	 *
	 * @param   string  $tpl  The name of the template file to parse
	 *
	 * @return  boolean  True on success
	 *
	 * @throws  \Exception  When the layout file is not found
	 */
	public function display($tpl = null)
	{
		$eventName = 'onBefore' . ucfirst($this->doTask);
		$this->triggerEvent($eventName, [$tpl]);

		$eventName = 'onAfter' . ucfirst($this->doTask);
		$this->triggerEvent($eventName, [$tpl]);

		return true;
	}

	/**
	 * The event which runs when we are displaying the record list JSON view
	 *
	 * @param   string  $tpl  The sub-template to use
	 */
	public function onBeforeBrowse($tpl = null)
	{
		// Load the model
		/** @var DataModel $model */
		$model = $this->getModel();

		$result = '';

		if (!$this->alreadyLoaded)
		{
			$this->limitStart = $model->getState('limitstart', 0);
			$this->limit      = $model->getState('limit', 0);
			$this->items      = $model->get(true, $this->limitStart, $this->limit);
			$this->total      = $model->count();
		}

		$document = $this->container->platform->getDocument();

		/** @var JsonDocument $document */
		if ($document instanceof JoomlaDocument)
		{
			$document->setMimeEncoding('application/json');
		}

		if (is_null($tpl))
		{
			$tpl = 'json';
		}

		$hasFailed = false;

		try
		{
			$result = $this->loadTemplate($tpl, true);

			if ($result instanceof \Exception)
			{
				$hasFailed = true;
			}
		}
		catch (\Exception $e)
		{
			$hasFailed = true;
		}

		if ($hasFailed)
		{
			// Default JSON behaviour in case the template isn't there!
			$result = [];

			foreach ($this->items as $item)
			{
				$result[] = (is_object($item) && method_exists($item, 'toArray')) ? $item->toArray() : $item;
			}

			$json = json_encode($result, JSON_PRETTY_PRINT);

			// JSONP support
			$callback = $this->input->get('callback', null, 'raw');

			if (!empty($callback))
			{
				echo $callback . '(' . $json . ')';
			}
			else
			{
				$defaultName = $this->input->get('view', 'main', 'cmd');
				$filename    = $this->input->get('basename', $defaultName, 'cmd');

				$document->setName($filename);
				echo $json;
			}
		}
		else
		{
			echo $result;
		}
	}

	/**
	 * The event which runs when we are displaying a single item JSON view
	 *
	 * @param   string  $tpl  The view sub-template to use
	 */
	protected function onBeforeRead($tpl = null)
	{
		self::renderSingleItem($tpl);
	}

	/**
	 * The event which runs when we are displaying a single item JSON view
	 *
	 * @param   string  $tpl  The view sub-template to use
	 */
	protected function onAfterSave($tpl = null)
	{
		self::renderSingleItem($tpl);
	}

	/**
	 * Renders a single item JSON view
	 *
	 * @param   string  $tpl  The view sub-template to use
	 */
	protected function renderSingleItem($tpl)
	{
		// Load the model
		/** @var DataModel $model */
		$model = $this->getModel();

		$result = '';

		if (!$this->alreadyLoaded)
		{
			$this->item = $model->find();
		}


		$document = $this->container->platform->getDocument();

		/** @var JsonDocument $document */
		if ($document instanceof JoomlaDocument)
		{
			$document->setMimeEncoding('application/json');
		}

		if (is_null($tpl))
		{
			$tpl = 'json';
		}

		$hasFailed = false;

		try
		{
			$result = $this->loadTemplate($tpl, true);

			if ($result instanceof \Exception)
			{
				$hasFailed = true;
			}
		}
		catch (\Exception $e)
		{
			$hasFailed = true;
		}

		if ($hasFailed)
		{
			$data = (is_object($this->item) && method_exists($this->item, 'toArray')) ? $this->item->toArray() : $this->item;

			$json = json_encode($data, JSON_PRETTY_PRINT);

			// JSONP support
			$callback = $this->input->get('callback');

			if (!empty($callback))
			{
				echo $callback . '(' . $json . ')';
			}
			else
			{
				$defaultName = $this->input->get('view', 'main', 'cmd');
				$filename    = $this->input->get('basename', $defaultName, 'cmd');
				$document->setName($filename);

				echo $json;
			}
		}
		else
		{
			echo $result;
		}
	}

	/**
	 * Convert an absolute URI to a relative one
	 *
	 * @param   string  $uri  The URI to convert
	 *
	 * @return  string  The relative URL
	 */
	protected function _removeURIBase($uri)
	{
		static $root = null, $rootlen = 0;

		if (is_null($root))
		{
			$root    = rtrim(Uri::base(false), '/');
			$rootlen = strlen($root);
		}

		if (substr($uri, 0, $rootlen) == $root)
		{
			$uri = substr($uri, $rootlen);
		}

		return ltrim($uri, '/');
	}

	/**
	 * Returns a Uri instance with a prototype URI used as the base for the
	 * other URIs created by the JSON renderer
	 *
	 * @return  Uri  The prototype Uri instance
	 */
	protected function _getPrototypeURIForPagination()
	{
		$protoUri = new Uri('index.php');
		$protoUri->setQuery($this->input->getData());
		$protoUri->delVar('savestate');
		$protoUri->delVar('base_path');

		return $protoUri;
	}
}
