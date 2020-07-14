<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form;

use FOF30\Container\Container;
use FOF30\Form\Header\HeaderBase;
use FOF30\Model\DataModel;
use FOF30\View\DataView\DataViewInterface;
use JFactory;
use JForm;
use Joomla\Registry\Registry;
use JText;
use SimpleXMLElement;

defined('_JEXEC') or die;

/**
 * Form is an extension to JForm which support not only edit views but also
 * browse (record list) and read (single record display) views based on XML
 * forms.
 *
 * @package  FrameworkOnFramework
 * @since    2.0
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Form extends JForm
{
	/**
	 * The model attached to this view
	 *
	 * @var DataModel
	 */
	protected $model;

	/**
	 * The view used to render this form
	 *
	 * @var DataViewInterface
	 */
	protected $view;

	/**
	 * The Container this form belongs to
	 *
	 * @var \FOF30\Container\Container
	 */
	protected $container;

	/**
	 * Map of entity objects for re-use.
	 * Prototypes for all fields and rules are here.
	 *
	 * Array's structure:
	 * <code>
	 * entities:
	 * {ENTITY_NAME}:
	 * {KEY}: {OBJECT}
	 * </code>
	 *
	 * @var    array
	 */
	protected $entities = array();

	/**
	 * Method to instantiate the form object.
	 *
	 * @param   Container $container The component Container where this form belongs to
	 * @param   string    $name      The name of the form.
	 * @param   array     $options   An array of form options.
	 */
	public function __construct(Container $container, $name, array $options = array())
	{
		parent::__construct($name, $options);

		$this->container = $container;
	}

	/**
	 * Returns the value of an attribute of the form itself
	 *
	 * @param   string $attribute The name of the attribute
	 * @param   mixed  $default   Optional default value to return
	 *
	 * @return  mixed
	 *
	 * @since 2.0
	 */
	public function getAttribute($attribute, $default = null)
	{
		$value = $this->xml->attributes()->$attribute;

		if (is_null($value))
		{
			return $default;
		}
		else
		{
			return (string)$value;
		}
	}

	/**
	 * Loads the CSS files defined in the form, based on its cssfiles attribute
	 *
	 * @return  void
	 *
	 * @since 2.0
	 */
	public function loadCSSFiles()
	{
		// Support for CSS files
		$cssfiles = $this->getAttribute('cssfiles');

		if (!empty($cssfiles))
		{
			$cssfiles = explode(',', $cssfiles);

			foreach ($cssfiles as $cssfile)
			{
				$this->getView()->addCssFile(trim($cssfile));
			}
		}

		// Support for LESS files
		$lessfiles = $this->getAttribute('lessfiles');

		if (!empty($lessfiles))
		{
			$lessfiles = explode(',', $lessfiles);

			foreach ($lessfiles as $def)
			{
				$parts = explode('||', $def, 2);
				$lessfile = $parts[0];
				$alt = (count($parts) > 1) ? trim($parts[1]) : null;
				$this->getView()->addLess(trim($lessfile), $alt);
			}
		}
	}

	/**
	 * Loads the Javascript files defined in the form, based on its jsfiles attribute
	 *
	 * @return  void
	 *
	 * @since 2.0
	 */
	public function loadJSFiles()
	{
		$jsfiles = $this->getAttribute('jsfiles');

		if (empty($jsfiles))
		{
			return;
		}

		$jsfiles = explode(',', $jsfiles);

		foreach ($jsfiles as $jsfile)
		{
			$this->getView()->addJavascriptFile(trim($jsfile));
		}
	}

	/**
	 * Returns a reference to the protected $data object, allowing direct
	 * access to and manipulation of the form's data.
	 *
	 * @return   \JRegistry|Registry  The form's data registry
	 *
	 * @since 2.0
	 */
	public function &getData()
	{
		return $this->data;
	}

	/**
	 * Method to load the form description from an XML file.
	 *
	 * The reset option works on a group basis. If the XML file references
	 * groups that have already been created they will be replaced with the
	 * fields in the new XML file unless the $reset parameter has been set
	 * to false.
	 *
	 * @param   string  $file   The filesystem path of an XML file.
	 * @param   bool    $reset  Flag to toggle whether form fields should be replaced if a field
	 *                          already exists with the same group/name.
	 * @param   bool    $xpath  An optional xpath to search for the fields.
	 *
	 * @return  boolean  True on success, false otherwise.
	 */
	public function loadFile($file, $reset = true, $xpath = false)
	{
		// Check to see if the path is an absolute path.
		if (!is_file($file))
		{
			return false;
		}

		// Attempt to load the XML file.
		$xml = simplexml_load_file($file);

		return $this->load($xml, $reset, $xpath);
	}

	/**
	 * Attaches a DataModel to this form
	 *
	 * @param   DataModel &$model The model to attach to the form
	 *
	 * @return  void
	 */
	public function setModel(DataModel &$model)
	{
		$this->model = $model;
	}

	/**
	 * Returns the DataModel attached to this form
	 *
	 * @return DataModel
	 */
	public function &getModel()
	{
		return $this->model;
	}

	/**
	 * Attaches a DataViewInterface to this form
	 *
	 * @param   DataViewInterface &$view The view to attach to the form
	 *
	 * @return  void
	 */
	public function setView(DataViewInterface &$view)
	{
		$this->view = $view;
	}

	/**
	 * Returns the DataViewInterface attached to this form
	 *
	 * @return DataViewInterface
	 */
	public function &getView()
	{
		return $this->view;
	}

	/**
	 * Method to get an array of FormHeader objects in the headerset.
	 *
	 * @return  array  The array of HeaderInterface objects in the headerset.
	 *
	 * @since   2.0
	 */
	public function getHeaderset()
	{
		$fields = array();

		$elements = $this->findHeadersByGroup();

		// If no field elements were found return empty.

		if (empty($elements))
		{
			return $fields;
		}

		// Build the result array from the found field elements.

		/** @var \SimpleXMLElement $element */
		foreach ($elements as $element)
		{
			// Get the field groups for the element.
			$attrs = $element->xpath('ancestor::headerset[@name]/@name');
			$groups = array_map('strval', $attrs ? $attrs : array());
			$group = implode('.', $groups);

			// If the field is successfully loaded add it to the result array.
			/** @var HeaderBase $field */
			if ($field = $this->loadHeader($element, $group))
			{
				$fields[$field->id] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Method to get an array of <header /> elements from the form XML document which are
	 * in a control group by name.
	 *
	 * @param   mixed   $group    The optional dot-separated form group path on which to find the fields.
	 *                            Null will return all fields. False will return fields not in a group.
	 * @param   boolean $nested   True to also include fields in nested groups that are inside of the
	 *                            group for which to find fields.
	 *
	 * @return  \SimpleXMLElement|bool  Boolean false on error or array of SimpleXMLElement objects.
	 *
	 * @since   2.0
	 */
	protected function &findHeadersByGroup($group = null, $nested = false)
	{
		$false = false;
		$fields = array();

		// Make sure there is a valid JForm XML document.
		if (!($this->xml instanceof \SimpleXMLElement))
		{
			return $false;
		}

		// Get only fields in a specific group?
		if ($group)
		{
			// Get the fields elements for a given group.
			$elements = &$this->findHeader($group);

			// Get all of the field elements for the fields elements.
			/** @var \SimpleXMLElement $element */
			foreach ($elements as $element)
			{
				// If there are field elements add them to the return result.
				if ($tmp = $element->xpath('descendant::header'))
				{
					// If we also want fields in nested groups then just merge the arrays.
					if ($nested)
					{
						$fields = array_merge($fields, $tmp);
					}

					// If we want to exclude nested groups then we need to check each field.
					else
					{
						$groupNames = explode('.', $group);

						foreach ($tmp as $field)
						{
							// Get the names of the groups that the field is in.
							$attrs = $field->xpath('ancestor::headers[@name]/@name');
							$names = array_map('strval', $attrs ? $attrs : array());

							// If the field is in the specific group then add it to the return list.
							if ($names == (array)$groupNames)
							{
								$fields = array_merge($fields, array($field));
							}
						}
					}
				}
			}
		}
		elseif ($group === false)
		{
			// Get only field elements not in a group.
			$fields = $this->xml->xpath('descendant::headers[not(@name)]/header | descendant::headers[not(@name)]/headerset/header ');
		}
		else
		{
			// Get an array of all the <header /> elements.
			$fields = $this->xml->xpath('//header');
		}

		return $fields;
	}

	/**
	 * Method to get a header field represented as a HeaderInterface object.
	 *
	 * @param   string $name  The name of the header field.
	 * @param   string $group The optional dot-separated form group path on which to find the field.
	 * @param   mixed  $value The optional value to use as the default for the field. (DEPRECATED)
	 *
	 * @return  HeaderInterface|bool  The HeaderInterface object for the field or boolean false on error.
	 *
	 * @since   2.0
	 */
	public function getHeader($name, $group = null, $value = null)
	{
		// Make sure there is a valid Form XML document.
		if (!($this->xml instanceof \SimpleXMLElement))
		{
			return false;
		}

		// Attempt to find the field by name and group.
		$element = $this->findHeader($name, $group);

		// If the field element was not found return false.
		if (!$element)
		{
			return false;
		}

		return $this->loadHeader($element, $group);
	}

	/**
	 * Method to get a header field represented as an XML element object.
	 *
	 * @param   string $name  The name of the form field.
	 * @param   string $group The optional dot-separated form group path on which to find the field.
	 *
	 * @return  mixed  The XML element object for the field or boolean false on error.
	 *
	 * @since   2.0
	 */
	protected function findHeader($name, $group = null)
	{
		$element = false;
		$fields = array();

		// Make sure there is a valid JForm XML document.
		if (!($this->xml instanceof \SimpleXMLElement))
		{
			return false;
		}

		// Let's get the appropriate field element based on the method arguments.
		if ($group)
		{
			// Get the fields elements for a given group.
			$elements = &$this->findGroup($group);

			// Get all of the field elements with the correct name for the fields elements.
			/** @var \SimpleXMLElement $element */
			foreach ($elements as $element)
			{
				// If there are matching field elements add them to the fields array.
				if ($tmp = $element->xpath('descendant::header[@name="' . $name . '"]'))
				{
					$fields = array_merge($fields, $tmp);
				}
			}

			// Make sure something was found.
			if (!$fields)
			{
				return false;
			}

			// Use the first correct match in the given group.
			$groupNames = explode('.', $group);

			/** @var \SimpleXMLElement $field */
			foreach ($fields as &$field)
			{
				// Get the group names as strings for ancestor fields elements.
				$attrs = $field->xpath('ancestor::headerfields[@name]/@name');
				$names = array_map('strval', $attrs ? $attrs : array());

				// If the field is in the exact group use it and break out of the loop.
				if ($names == (array)$groupNames)
				{
					$element = &$field;
					break;
				}
			}
		}
		else
		{
			// Get an array of fields with the correct name.
			$fields = $this->xml->xpath('//header[@name="' . $name . '"]');

			// Make sure something was found.
			if (!$fields)
			{
				return false;
			}

			// Search through the fields for the right one.
			foreach ($fields as &$field)
			{
				// If we find an ancestor fields element with a group name then it isn't what we want.
				if ($field->xpath('ancestor::headerfields[@name]'))
				{
					continue;
				}

				// Found it!
				else
				{
					$element = &$field;
					break;
				}
			}
		}

		return $element;
	}

	/**
	 * Method to load, setup and return a HeaderInterface object based on field data.
	 *
	 * @param   string $element The XML element object representation of the form field.
	 * @param   string $group   The optional dot-separated form group path on which to find the field.
	 *
	 * @return  HeaderInterface|bool  The HeaderInterface object for the field or boolean false on error.
	 *
	 * @since   2.0
	 */
	protected function loadHeader($element, $group = null)
	{
		// Make sure there is a valid SimpleXMLElement.
		if (!($element instanceof \SimpleXMLElement))
		{
			return false;
		}

		// Get the field type.
		$type = $element['type'] ? (string)$element['type'] : 'field';

		// Load the JFormField object for the field.
		$field = $this->loadHeaderType($type);

		// If the object could not be loaded, get a text field object.
		if ($field === false)
		{
			$field = $this->loadHeaderType('field');
		}

		// Setup the HeaderInterface object.
		$field->setForm($this);

		if ($field->setup($element, $group))
		{
			return $field;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to remove a header from the form definition.
	 *
	 * @param   string  $name   The name of the form field for which remove.
	 * @param   string  $group  The optional dot-separated form group path on which to find the field.
	 *
	 * @return  boolean  True on success, false otherwise.
	 *
	 * @throws  \UnexpectedValueException
	 */
	public function removeHeader($name, $group = null)
	{
		// Make sure there is a valid JForm XML document.
		if (!($this->xml instanceof SimpleXMLElement))
		{
			throw new \UnexpectedValueException(sprintf('%s::getFieldAttribute `xml` is not an instance of SimpleXMLElement', get_class($this)));
		}

		// Find the form field element from the definition.
		$element = $this->findHeader($name, $group);

		// If the element exists remove it from the form definition.
		if ($element instanceof SimpleXMLElement)
		{
			$dom = dom_import_simplexml($element);
			$dom->parentNode->removeChild($dom);

			return true;
		}

		return false;
	}

	/**
	 * Proxy for {@link Helper::loadFieldType()}.
	 *
	 * @param   string  $type The field type.
	 * @param   boolean $new  Flag to toggle whether we should get a new instance of the object.
	 *
	 * @return  FieldInterface|bool  FieldInterface object on success, false otherwise.
	 *
	 * @since   2.0
	 */
	protected function loadFieldType($type, $new = true)
	{
		return $this->loadType('field', $type, $new);
	}

	/**
	 * Proxy for {@link Helper::loadHeaderType()}.
	 *
	 * @param   string  $type The field type.
	 * @param   boolean $new  Flag to toggle whether we should get a new instance of the object.
	 *
	 * @return  HeaderInterface|bool  HeaderInterface object on success, false otherwise.
	 *
	 * @since   2.0
	 */
	protected function loadHeaderType($type, $new = true)
	{
		return $this->loadType('header', $type, $new);
	}

	/**
	 * Proxy for {@link Helper::loadRuleType()}.
	 *
	 * @param   string  $type The rule type.
	 * @param   boolean $new  Flag to toggle whether we should get a new instance of the object.
	 *
	 * @return  \JFormRule|bool  JFormRule object on success, false otherwise.
	 *
	 * @see     Helper::loadRuleType()
	 * @since   2.0
	 */
	protected function loadRuleType($type, $new = true)
	{
		return $this->loadType('rule', $type, $new);
	}

	/**
	 * Method to load a form entity object given a type.
	 * Each type is loaded only once and then used as a prototype for other objects of same type.
	 * Please, use this method only with those entities which support types (forms don't support them).
	 *
	 * @param   string  $entity The entity.
	 * @param   string  $type   The entity type.
	 * @param   boolean $new    Flag to toggle whether we should get a new instance of the object.
	 *
	 * @return  mixed Entity object on success, false otherwise.
	 */
	protected function loadType($entity, $type, $new = true)
	{
		// Reference to an array with current entity's type instances
		$types = &$this->entities[$entity];

		// Return an entity object if it already exists and we don't need a new one.
		if (isset($types[$type]) && $new === false)
		{
			return $types[$type];
		}

		$class = $this->loadClass($entity, $type);

		if ($class !== false)
		{
			// Instantiate a new type object.
			$types[$type] = new $class;

			return $types[$type];
		}
		else
		{
			return false;
		}
	}

	/**
	 * Load a class for one of the form's entities of a particular type.
	 * Currently, it makes sense to use this method for the "field" and "rule" entities
	 * (but you can support more entities in your subclass).
	 *
	 * @param   string $entity One of the form entities (field, header or rule).
	 * @param   string $type   Type of an entity.
	 *
	 * @return  mixed  Class name on success or false otherwise.
	 *
	 * @since   2.0
	 */
	public function loadClass($entity, $type)
	{
		// Get the prefixes for namespaced classes (FOF3 way)
		$namespacedPrefixes = array(
			$this->container->getNamespacePrefix(),
			'FOF30\\',
		);

		// Get the prefixes for non-namespaced classes (FOF2 and Joomla! way)
		$plainPrefixes = array('J');

		// If the type is given as prefix.type add the custom type into the two prefix arrays
		if (strpos($type, '.'))
		{
			list($prefix, $type) = explode('.', $type);

			array_unshift($plainPrefixes, $prefix);
			array_unshift($namespacedPrefixes, $prefix);
		}

		// First try to find the namespaced class
		foreach ($namespacedPrefixes as $prefix)
		{
			$class = rtrim($prefix, '\\') . '\\Form\\' . ucfirst($entity) . '\\' . ucfirst($type);

			if (class_exists($class, true))
			{
				return $class;
			}
		}

		// TODO The rest of the code is legacy and will be removed in a future version

		// Then try to find the non-namespaced class
		$classes = array();

		foreach ($plainPrefixes as $prefix)
		{
			$class = \JString::ucfirst($prefix, '_') . 'Form' . \JString::ucfirst($entity, '_') . \JString::ucfirst($type, '_');

			if (class_exists($class, true))
			{
				return $class;
			}

			$classes[] = $class;
		}

		// Get the field search path array.
		$reflector = new \ReflectionClass('\\JFormHelper');
		$addPathMethod = $reflector->getMethod('addPath');
		$addPathMethod->setAccessible(true);
		$paths = $addPathMethod->invoke(null, $entity);

		// If the type is complex, add the base type to the paths.
		if ($pos = strpos($type, '_'))
		{
			// Add the complex type prefix to the paths.
			for ($i = 0, $n = count($paths); $i < $n; $i++)
			{
				// Derive the new path.
				$path = $paths[$i] . '/' . strtolower(substr($type, 0, $pos));

				// If the path does not exist, add it.
				if (!in_array($path, $paths))
				{
					$paths[] = $path;
				}
			}

			// Break off the end of the complex type.
			$type = substr($type, $pos + 1);
		}

		// Try to find the class file.
		$type = strtolower($type) . '.php';

		foreach ($paths as $path)
		{
			if ($file = \JPath::find($path, $type))
			{
				require_once $file;

				foreach ($classes as $class)
				{
					if (class_exists($class, false))
					{
						return $class;
					}
				}
			}
		}

		return false;
	}

	/**
	 * WARNING: THIS IS IGNORED IN FOF3!
	 *
	 * @param   string  $new  IGNORED!
	 *
	 * @return  void
	 *
	 * @deprecated 3.0
	 */
	public static function addFieldPath($new = null)
	{
		if ($new) {}; // Prevents phpStorm from freaking out about the unused $new parameter...

		if (class_exists('JLog'))
		{
			\JLog::add(__CLASS__ . '::' . __METHOD__ . '() is deprecated since FOF 3.0 and should not be used.', \JLog::WARNING, 'deprecated');
		}
	}

	/**
	 * WARNING: THIS IS IGNORED IN FOF3!
	 *
	 * @param   string  $new  IGNORED!
	 *
	 * @return  void
	 *
	 * @deprecated 3.0
	 */
	public static function addHeaderPath($new = null)
	{
		if ($new) {}; // Prevents phpStorm from freaking out about the unused $new parameter...

		if (class_exists('JLog'))
		{
			\JLog::add(__CLASS__ . '::' . __METHOD__ . '() is deprecated since FOF 3.0 and should not be used.', \JLog::WARNING, 'deprecated');
		}
	}

	/**
	 * WARNING: THIS IS IGNORED IN FOF3!
	 *
	 * @param   string  $new  IGNORED!
	 *
	 * @return  void
	 *
	 * @deprecated 3.0
	 */
	public static function addFormPath($new = null)
	{
		if ($new) {}; // Prevents phpStorm from freaking out about the unused $new parameter...

		if (class_exists('JLog'))
		{
			\JLog::add(__CLASS__ . '::' . __METHOD__ . '() is deprecated since FOF 3.0 and should not be used.', \JLog::WARNING, 'deprecated');
		}
	}

	/**
	 * WARNING: THIS IS IGNORED IN FOF3!
	 *
	 * @param   string  $new  IGNORED!
	 *
	 * @return  void
	 *
	 * @deprecated 3.0
	 */
	public static function addRulePath($new = null)
	{
		if ($new) {}; // Prevents phpStorm from freaking out about the unused $new parameter...

		if (class_exists('JLog'))
		{
			\JLog::add(__CLASS__ . '::' . __METHOD__ . '() is deprecated since FOF 3.0 and should not be used.', \JLog::WARNING, 'deprecated');
		}
	}

	/**
	 * Get a reference to the form's Container
	 *
	 * @return Container
	 */
	public function &getContainer()
	{
		return $this->container;
	}

	/**
	 * Set the form's Container
	 *
	 * @param Container $container
	 */
	public function setContainer($container)
	{
		$this->container = $container;
	}

	/**
	 * Method to bind data to the form.
	 *
	 * @param   mixed  $data  An array or object of data to bind to the form.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   11.1
	 */
	public function bind($data)
	{
		$this->data = class_exists('JRegistry') ? new \JRegistry() : new Registry();

		if (is_object($data) && ($data instanceof DataModel))
		{
			$maxDepth = (int) $this->getAttribute('relation_depth', '1');

			return parent::bind($this->modelToBindSource($data, $maxDepth));
		}

		return parent::bind($data);
	}

	/**
	 * Method to bind data to the form for the group level.
	 *
	 * @param   string  $group  The dot-separated form group path on which to bind the data.
	 * @param   mixed   $data   An array or object of data to bind to the form for the group level.
	 *
	 * @return  void
	 *
	 * @since   11.1
	 */
	protected function bindLevel($group, $data)
	{
		if (is_object($data) && ($data instanceof DataModel))
		{
			parent::bindLevel($group, $this->modelToBindSource($data));

			return;
		}

		parent::bindLevel($group, $data);
	}

	/**
	 * Method to load, setup and return a JFormField object based on field data.
	 *
	 * @param   string  $element  The XML element object representation of the form field.
	 * @param   string  $group    The optional dot-separated form group path on which to find the field.
	 * @param   mixed   $value    The optional value to use as the default for the field.
	 *
	 * @return  mixed  The JFormField object for the field or boolean false on error.
	 *
	 * @since   11.1
	 */
	protected function loadField($element, $group = null, $value = null)
	{
		// Make sure there is a valid SimpleXMLElement.
		if (!($element instanceof SimpleXMLElement))
		{
			return false;
		}

		// Get the field type.
		$type = $element['type'] ? (string) $element['type'] : 'text';

		// Load the JFormField object for the field.
		$field = $this->loadFieldType($type);

		// If the object could not be loaded, get a text field object.
		if ($field === false)
		{
			$field = $this->loadFieldType('text');
		}

		/*
		 * Get the value for the form field if not set.
		 * Default to the translated version of the 'default' attribute
		 * if 'translate_default' attribute if set to 'true' or '1'
		 * else the value of the 'default' attribute for the field.
		 */
		if ($value === null)
		{
			$default = (string) $element['default'];

			if (($translate = $element['translate_default']) && ((string) $translate == 'true' || (string) $translate == '1'))
			{
				$lang = JFactory::getLanguage();

				if ($lang->hasKey($default))
				{
					$debug = $lang->setDebug(false);
					$default = JText::_($default);
					$lang->setDebug($debug);
				}
				else
				{
					$default = JText::_($default);
				}
			}

			$getValueFrom = (isset($element['name_from'])) ? (string) $element['name_from'] : (string) $element['name'];

			$value = $this->getValue($getValueFrom, $group, $default);
		}

		// Setup the JFormField object.
		$field->setForm($this);

		if ($field->setup($element, $value, $group))
		{
			return $field;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Method to get a form field represented as an XML element object.
	 *
	 * @param   string  $name   The name of the form field.
	 * @param   string  $group  The optional dot-separated form group path on which to find the field.
	 *
	 * @return  mixed  The XML element object for the field or boolean false on error.
	 *
	 * @since   11.1
	 */
	protected function findField($name, $group = null)
	{
		$element = false;
		$fields = array();

		// Make sure there is a valid JForm XML document.
		if (!($this->xml instanceof SimpleXMLElement))
		{
			return false;
		}

		// Let's get the appropriate field element based on the method arguments.
		if ($group)
		{
			// Get the fields elements for a given group.
			$elements = &$this->findGroup($group);

			// Get all of the field elements with the correct name for the fields elements.
			/** @var SimpleXMLElement $element */
			foreach ($elements as $element)
			{
				// If there are matching field elements add them to the fields array.
				if ($tmp = $element->xpath('descendant::field[@name="' . $name . '"]'))
				{
					$fields = array_merge($fields, $tmp);
				}
				elseif ($tmp = $element->xpath('descendant::field[@name_from="' . $name . '"]'))
				{
					$fields = array_merge($fields, $tmp);
				}
			}

			// Make sure something was found.
			if (!$fields)
			{
				return false;
			}

			// Use the first correct match in the given group.
			$groupNames = explode('.', $group);

			/** @var SimpleXMLElement $field */
			foreach ($fields as &$field)
			{
				// Get the group names as strings for ancestor fields elements.
				$attrs = $field->xpath('ancestor::fields[@name]/@name');
				$names = array_map('strval', $attrs ? $attrs : array());

				// If the field is in the exact group use it and break out of the loop.
				if ($names == (array) $groupNames)
				{
					$element = &$field;
					break;
				}
			}
		}
		else
		{
			// Get an array of fields with the correct name.
			$fields = $this->xml->xpath('//field[@name="' . $name . '"]');

			if (!$fields)
			{
				$fields = array();
			}

			$fieldsNameFrom = $this->xml->xpath('//field[@name_from="' . $name . '"]');

			if ($fieldsNameFrom)
			{
				$fields = array_merge($fields, $fieldsNameFrom);
			}

			// Make sure something was found.
			if (empty($fields))
			{
				return false;
			}

			// Search through the fields for the right one.
			foreach ($fields as &$field)
			{
				// If we find an ancestor fields element with a group name then it isn't what we want.
				if ($field->xpath('ancestor::fields[@name]'))
				{
					continue;
				}

				// Found it!
				else
				{
					$element = &$field;
					break;
				}
			}
		}

		return $element;
	}

	/**
	 * Converts a DataModel into data suitable for use with the form. The difference to the Model's getData() method is
	 * that we process hasOne and belongsTo relations. This is a recursive function which will be called at most
	 * $maxLevel deep. You can set this in the form XML file, in the relation_depth attribute.
	 *
	 * The $modelsProcessed array which is passed in successive recursions lets us prevent pointless Inception-style
	 * recursions, e.g. Model A is related to Model B is related to Model C is related to Model A. You clearly don't
	 * care to see a.b.c.a.b in the results. You just want a.b.c. Obviously c is indirectly related to a because that's
	 * where you began the recursion anyway.
	 *
	 * @param   DataModel  $model            The item to dump its contents into an array
	 * @param   int        $maxLevel         Maximum nesting level of relations to process. Default: 1.
	 * @param   array      $modelsProcessed  Array of the fully qualified model class names already processed.
	 *
	 * @return  array
	 * @throws  DataModel\Relation\Exception\RelationNotFound
	 */
	protected function modelToBindSource(DataModel $model, $maxLevel = 1, $modelsProcessed = array())
	{
		$maxLevel--;

		$data = $model->toArray();

		$relations = $model->getRelations()->getRelationNames();
		$relationTypes = $model->getRelations()->getRelationTypes();
		$relationTypes = array_map(function ($x) {
			return ltrim($x, '\\');
		}, $relationTypes);
		$relationTypes = array_flip($relationTypes);

		if (is_array($relations) && count($relations) && ($maxLevel >= 0))
		{
			foreach ($relations as $relationName)
			{
				$rel = $model->getRelations()->getRelation($relationName);
				$class = get_class($rel);

				if (!isset($relationTypes[$class]))
				{
					continue;
				}

				if (!in_array($relationTypes[$class], array('hasOne', 'belongsTo')))
				{
					continue;
				}

				/** @var DataModel $relData */
				$relData = $model->$relationName;

				if (!($relData instanceof DataModel))
				{
					continue;
				}

				$modelType = get_class($relData);

				if (in_array($modelType, $modelsProcessed))
				{
					continue;
				}

				$modelsProcessed[] = $modelType;

				$relDataArray = $this->modelToBindSource($relData, $maxLevel, $modelsProcessed);

				if (!is_array($relDataArray) || empty($relDataArray))
				{
					continue;
				}

				foreach ($relDataArray as $k => $v)
				{
					$data[$relationName . '.' . $k] = $v;
				}
			}
		}

		return $data;
	}


}
