<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\View\DataView;

use FOF30\Hal\Document;
use FOF30\Hal\Link;
use FOF30\Model\DataModel;

defined('_JEXEC') or die;

class Json extends Raw implements DataViewInterface
{
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
	protected $item = null;

	/**
	 * When set to true we'll add hypermedia to the output, implementing the
	 * HAL specification (http://stateless.co/hal_specification.html)
	 *
	 * @var   boolean
	 */
	public $useHypermedia = false;

	/**
	 * Set to true if your onBefore* methods have already populated the item, items, limitstart etc properties used to
	 * render a JSON document.
	 *
	 * @var bool
	 */
	public $alreadyLoaded = false;

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
		$this->triggerEvent($eventName, array($tpl));

		$eventName = 'onAfter' . ucfirst($this->doTask);
		$this->triggerEvent($eventName, array($tpl));

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
			$this->limit = $model->getState('limit', 0);
			$this->items = $model->get(true, $this->limitStart, $this->limit);
			$this->total = $model->count();
		}

		$document = $this->container->platform->getDocument();

		/** @var \JDocumentJSON $document */
		if ($document instanceof \JDocument)
		{
			if ($this->useHypermedia)
			{
				$document->setMimeEncoding('application/hal+json');
			}
			else
			{
				$document->setMimeEncoding('application/json');
			}
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
			if ($this->useHypermedia)
			{
                $data = array();

                foreach($this->items as $item)
                {
                    if(is_object($item) && method_exists($item, 'toArray'))
                    {
                        $data[] = $item->toArray();
                    }
                    else
                    {
                        $data[] = $item;
                    }
                }

				$HalDocument = $this->_createDocumentWithHypermedia($data, $model);
				$json = $HalDocument->render('json');
			}
			else
			{
                $result = array();

                foreach($this->items as $item)
                {
                    if(is_object($item) && method_exists($item, 'toArray'))
                    {
                        $result[] = $item->toArray();
                    }
                    else
                    {
                        $result[] = $item;
                    }
                }

				if (version_compare(PHP_VERSION, '5.4', 'ge'))
				{
					$json = json_encode($result, JSON_PRETTY_PRINT);
				}
				else
				{
					$json = json_encode($result);
				}
			}

			// JSONP support
			$callback = $this->input->get('callback', null, 'raw');

			if (!empty($callback))
			{
				echo $callback . '(' . $json . ')';
			}
			else
			{
				$defaultName = $this->input->get('view', 'main', 'cmd');
				$filename = $this->input->get('basename', $defaultName, 'cmd');

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
	protected function renderSingleItem($tpl) {
		// Load the model
		/** @var DataModel $model */
		$model = $this->getModel();

		$result = '';

		if (!$this->alreadyLoaded)
		{
			$this->item = $model->find();
		}


		$document = $this->container->platform->getDocument();

		/** @var \JDocumentJSON $document */
		if ($document instanceof \JDocument)
		{
			if ($this->useHypermedia)
			{
				$document->setMimeEncoding('application/hal+json');
			}
			else
			{
				$document->setMimeEncoding('application/json');
			}
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

			if ($this->useHypermedia)
			{
				$haldocument = $this->_createDocumentWithHypermedia($this->item, $model);
				$json = $haldocument->render('json');
			}
			else
			{
                if (is_object($this->item) && method_exists($this->item, 'toArray'))
                {
                    $data = $this->item->toArray();
                }
                else
                {
                    $data = $this->item;
                }

				if (version_compare(PHP_VERSION, '5.4', 'ge'))
				{
					$json = json_encode($data, JSON_PRETTY_PRINT);
				}
				else
				{
					$json = json_encode($data);
				}

			}

			// JSONP support
			$callback = $this->input->get('callback', null);

			if (!empty($callback))
			{
				echo $callback . '(' . $json . ')';
			}
			else
			{
				$defaultName = $this->input->get('view', 'main', 'cmd');
				$filename = $this->input->get('basename', $defaultName, 'cmd');
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
	 * Creates a \FOF30\Hal\Document using the provided data
	 *
	 * @param   mixed|array  $data   The data to put in the document
	 * @param   DataModel    $model  The model of this view
	 *
	 * @return  \FOF30\Hal\Document  A HAL-enabled document
	 */
	protected function _createDocumentWithHypermedia($data, $model = null)
	{
		// Create a new HAL document

		if (is_array($data))
		{
			$count = count($data);
		}
		else
		{
			$count = null;
		}

		if ($count == 1)
		{
			reset($data);
			$document = new Document(end($data));
		}
		else
		{
			$document = new Document($data);
		}

		// Create a self link
		$uri = (string) (\JUri::getInstance());
		$uri = $this->_removeURIBase($uri);
		$uri = \JRoute::_($uri);
		$document->addLink('self', new Link($uri));

		// Create relative links in a record list context
		if (is_array($data) && ($model instanceof DataModel))
		{
            if(!isset($this->total))
            {
                $this->total = $model->count();
            }

            if(!isset($this->limitStart))
            {
                $this->limitStart = $model->getState('limitstart', 0);
            }

            if(!isset($this->limit))
            {
                $this->limit = $model->getState('limit', 0);
            }

			$pagination = new \JPagination($this->total, $this->limitStart, $this->limit);

			if ($pagination->pagesTotal > 1)
			{
				// Try to guess URL parameters and create a prototype URL
				// NOTE: You are better off specialising this method
				$protoUri = $this->_getPrototypeURIForPagination();

				// The "first" link
				$uri = clone $protoUri;
				$uri->setVar('limitstart', 0);
				$uri = \JRoute::_($uri);

				$document->addLink('first', new Link($uri));

				// Do we need a "prev" link?
				if ($pagination->pagesCurrent > 1)
				{
					$prevPage = $pagination->pagesCurrent - 1;
					$limitstart = ($prevPage - 1) * $pagination->limit;
					$uri = clone $protoUri;
					$uri->setVar('limitstart', $limitstart);
					$uri = \JRoute::_($uri);

					$document->addLink('prev', new Link($uri));
				}

				// Do we need a "next" link?
				if ($pagination->pagesCurrent < $pagination->pagesTotal)
				{
					$nextPage = $pagination->pagesCurrent + 1;
					$limitstart = ($nextPage - 1) * $pagination->limit;
					$uri = clone $protoUri;
					$uri->setVar('limitstart', $limitstart);
					$uri = \JRoute::_($uri);

					$document->addLink('next', new Link($uri));
				}

				// The "last" link?
				$lastPage = $pagination->pagesTotal;
				$limitstart = ($lastPage - 1) * $pagination->limit;
				$uri = clone $protoUri;
				$uri->setVar('limitstart', $limitstart);
				$uri = \JRoute::_($uri);

				$document->addLink('last', new Link($uri));
			}
		}

		return $document;
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
			$root = rtrim(\JUri::base(false), '/');
			$rootlen = strlen($root);
		}

		if (substr($uri, 0, $rootlen) == $root)
		{
			$uri = substr($uri, $rootlen);
		}

		return ltrim($uri, '/');
	}

	/**
	 * Returns a JUri instance with a prototype URI used as the base for the
	 * other URIs created by the JSON renderer
	 *
	 * @return  \JUri  The prototype JUri instance
	 */
	protected function _getPrototypeURIForPagination()
	{
		$protoUri = new \JUri('index.php');
		$protoUri->setQuery($this->input->getData());
		$protoUri->delVar('savestate');
		$protoUri->delVar('base_path');

		return $protoUri;
	}
}
