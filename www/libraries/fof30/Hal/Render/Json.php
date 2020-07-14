<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Hal\Render;

use FOF30\Hal\Document;
use FOF30\Hal\Link;
use FOF30\Model\DataModel;

defined('_JEXEC') or die;

/**
 * Implements the HAL over JSON renderer
 *
 * @see http://stateless.co/hal_specification.html
 */
class Json implements RenderInterface
{
	/**
	 * When data is an array we'll output the list of data under this key
	 *
	 * @var   string
	 */
	private $_dataKey = '_list';

	/**
	 * The document to render
	 *
	 * @var   Document
	 */
	protected $_document;

	/**
	 * Public constructor
	 *
	 * @param   Document  &$document  The document to render
	 */
	public function __construct(Document &$document)
	{
		$this->_document = $document;
	}

	/**
	 * Render a HAL document in JSON format
	 *
	 * @param   array  $options  Rendering options. You can currently only set json_options (json_encode options)
	 *
	 * @return  string  The JSON representation of the HAL document
	 */
	public function render($options = array())
	{
		if (isset($options['data_key']))
		{
			$this->_dataKey = $options['data_key'];
		}

		if (isset($options['json_options']))
		{
			$jsonOptions = $options['json_options'];
		}
		else
		{
			$jsonOptions = 0;
		}

		$serialiseThis = new \stdClass;

		// Add links
		$collection = $this->_document->getLinks();
		$serialiseThis->_links = new \stdClass;

		foreach ($collection as $rel => $links)
		{
			if (!is_array($links))
			{
				$serialiseThis->_links->$rel = $this->_getLink($links);
			}
			else
			{
				$serialiseThis->_links->$rel = array();

				foreach ($links as $link)
				{
					array_push($serialiseThis->_links->$rel, $this->_getLink($link));
				}
			}
		}

		// Add embedded documents

		$collection = $this->_document->getEmbedded();

		if (!empty($collection))
		{
			$serialiseThis->_embedded = new \stdClass;

			foreach ($collection as $rel => $embeddeddocs)
			{
				$serialiseThis->_embedded->$rel = array();

				if (!is_array($embeddeddocs))
				{
					$embeddeddocs = array($embeddeddocs);
				}

				foreach ($embeddeddocs as $embedded)
				{
					$renderer = new static($embedded);
					array_push($serialiseThis->_embedded->$rel, $renderer->render($options));
				}
			}
		}

		// Add data
		$data = $this->_document->getData();

		if (is_object($data))
		{
			if ($data instanceof DataModel)
			{
				$data = $data->toArray();
			}
			else
			{
				$data = (array) $data;
			}

			if (!empty($data))
			{
				foreach ($data as $k => $v)
				{
					$serialiseThis->$k = $v;
				}
			}
		}
		elseif (is_array($data))
		{
			$serialiseThis->{$this->_dataKey} = $data;
		}

		return json_encode($serialiseThis, $jsonOptions);
	}

	/**
	 * Converts a FOFHalLink object into a stdClass object which will be used
	 * for JSON serialisation
	 *
	 * @param   Link  $link  The link you want converted
	 *
	 * @return  \stdClass  The converted link object
	 */
	protected function _getLink(Link $link)
	{
		$ret = array(
			'href'	=> $link->href
		);

		if ($link->templated)
		{
			$ret['templated'] = 'true';
		}

		if ($link->name)
		{
			$ret['name'] = $link->name;
		}

		if ($link->hreflang)
		{
			$ret['hreflang'] = $link->hreflang;
		}

		if ($link->title)
		{
			$ret['title'] = $link->title;
		}

		return (object) $ret;
	}
}
