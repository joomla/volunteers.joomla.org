<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Factory\Scaffolding\Layout;

use FOF30\Model\DataModel;

defined('_JEXEC') or die;

/**
 * Class BaseErector
 * @package FOF30\Factory\Scaffolding\Layout
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class BaseErector implements ErectorInterface
{
	/**
	 * The Builder which called us
	 *
	 * @var \FOF30\Factory\Scaffolding\Layout\Builder
	 */
	protected $builder = null;

	/**
	 * The Model attached to the view we're building
	 *
	 * @var \FOF30\Model\DataModel
	 */
	protected $model = null;

	/**
	 * The name of our view
	 *
	 * @var string
	 */
	protected $viewName = null;

	/**
	 * The XML document we're constructing
	 *
	 * @var  \SimpleXMLElement
	 */
	protected $xml;

	/**
	 * The common language key prefix, e.g. COM_EXAMPLE_MYVIEW_
	 *
	 * @var null
	 */
	private $langKeyPrefix = null;

	/**
	 * Strings to add to the language definition
	 *
	 * @var array
	 */
	private $strings = array();

	/**
	 * Construct the erector object
	 *
	 * @param   \FOF30\Factory\Scaffolding\Layout\Builder $parent   The parent builder
	 * @param   \FOF30\Model\DataModel             $model    The model we're erecting a scaffold against
	 * @param   string                             $viewName The view name for this model
	 */
	public function __construct(Builder $parent, DataModel $model, $viewName)
	{
		$this->builder = $parent;
		$this->model = $model;
		$this->viewName = $viewName;

		$this->xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><form></form>');
	}

	/**
	 * Erects a scaffold. It then uses the parent's setXml and setStrings to assign the erected scaffold and the
	 * additional language strings to the parent which will decide what to do with that.
	 *
	 * @return  void
	 *
	 * @throws  \LogicException  Because it's not implemented
	 */
	public function build()
	{
		throw new \LogicException('You need to implement build() in your Erector class');
	}

	/**
	 * Returns the common language key prefix, something like "COM_EXAMPLE_MYVIEW_"
	 *
	 * @return string
	 */
	protected function getLangKeyPrefix()
	{
		if (empty($this->langKeyPrefix))
		{
			$prefix = $key = $this->builder->getContainer()->componentName . '_'
				. $this->viewName . '_';
			$this->langKeyPrefix = strtoupper($prefix);
		}

		return $this->langKeyPrefix;
	}

	/**
	 * Returns the language definition for a field. The hashed array has two keys, label and desc, each one containing
	 * the language definition for the label and description of the field. Each definition has the keys key and value
	 * with the language key and actual language string.
	 *
	 * @param   string  $fieldName
	 *
	 * @return array
	 */
	protected function getFieldLabel($fieldName)
	{
		$fieldNameForKey = strtoupper($fieldName);

		$definition = array(
			'label' => array(
				'key' => $this->getLangKeyPrefix() . $fieldNameForKey . '_LABEL',
				'value' => ucfirst($fieldName),
			),
			'desc' => array(
				'key' => $this->getLangKeyPrefix() . $fieldNameForKey . '_DESC',
				'value' => 'Description for ' . ucfirst($fieldName),
			)
		);

		return $definition;
	}

	/**
	 * Convert the database type into something we can use
	 *
	 * @param   string  $type  The type of the database field
	 *
	 * @return  array
	 */
	public static function getFieldType($type)
	{
		if (empty($type))
		{
			return null;
		}

		// Remove parentheses, indicating field options / size (they don't matter in type detection)
		if (strpos($type, '(') === false)
		{
			$type .= '()';
		}

		list($type, $parameters) = explode('(', $type);

		$detectedType = null;
		$detectedParameters = null;

		$type = strtolower($type);

		switch (trim($type))
		{
			case 'varchar':
			case 'text':
			case 'char':
			case 'character varying':
			case 'nvarchar':
			case 'nchar':
				$detectedType = 'Text';
				break;

			case 'smalltext':
			case 'longtext':
			case 'mediumtext':
				$detectedType = 'Text';
				break;

			case 'date':
			case 'datetime':
			case 'time':
			case 'year':
			case 'timestamp':
			case 'timestamp without time zone':
			case 'timestamp with time zone':
				$detectedType = 'Calendar';
				break;

			case 'tinyint':
			case 'smallint':
				$detectedType = 'Checkbox';
				break;

			case 'int':
			case 'integer':
			case 'bigint':
				// Because the Integer field is rendered in Joomla! as a drop-down list. Ugh!!!
				$detectedType = 'Number';
				break;

			case 'float':
			case 'double':
			case 'currency':
				$detectedType = 'Number';
				break;

			case 'enum':
				$detectedType = 'GenericList';
				$parameters = trim($parameters, "\t\n\r\0\x0B )");
				$detectedParameters = explode(',', $parameters);
				$detectedParameters = array_map(function ($x) { return trim($x, "'\n\r\t\0\x0B"); }, $detectedParameters);
				$temp = array();
				foreach ($detectedParameters as $v)
				{
					$temp[$v] = $v;
				}
				$detectedParameters = $temp;
				break;
		}

		// Sometimes we have character types followed by a space and some cruft. Let's handle them.
		if (is_null($detectedType) && !empty($type))
		{
			list ($type, ) = explode(' ', $type);

			switch (trim($type))
			{
				case 'varchar':
				case 'text':
				case 'char':
				case 'character varying':
				case 'nvarchar':
				case 'nchar':
					$detectedType = 'Text';
					break;

				case 'smalltext':
				case 'longtext':
				case 'mediumtext':
					$detectedType = 'Text';
					break;

				case 'date':
				case 'datetime':
				case 'time':
				case 'year':
				case 'timestamp':
					$detectedType = 'Calendar';
					break;

				case 'tinyint':
				case 'smallint':
					$detectedType = 'Checkbox';
					break;

				default:
					$detectedType = 'Integer';
					break;
			}
		}

		// If all else fails assume it's a Text and hope for the best
		if (empty($detectedType))
		{
			$detectedType = 'Text';
		}

		return array('type' => $detectedType, 'params' => $detectedParameters);
	}

	/**
	 * Adds a language string definition as long as it doesn't exist in the existing language file.
	 *
	 * @param   string  $key    The language string key
	 * @param   string  $value  The language string
	 */
	protected function addString($key, $value)
	{
		if (\JText::_($key) != $key)
		{
			return;
		}

		$this->strings[$key] = $value;
	}

	/**
	 * Push the form and strings to the builder
	 */
	protected function pushResults()
	{
		$this->builder->setStrings($this->strings);
		$this->builder->setXml($this->xml);
	}
}
