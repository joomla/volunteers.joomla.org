<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Form\Field;

use FOF30\Form\FieldInterface;
use FOF30\Form\Form;
use FOF30\Model\DataModel;
use JText;

defined('_JEXEC') or die;

\JFormHelper::loadFieldClass('list');

/**
 * Form Field class for FOF
 * Components installed on the site
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class Components extends \JFormFieldList implements FieldInterface
{
	/**
	 * @var  string  Static field output
	 */
	protected $static;

	/**
	 * @var  string  Repeatable field output
	 */
	protected $repeatable;

	/**
	 * The Form object of the form attached to the form field.
	 *
	 * @var    Form
	 */
	protected $form;

	/**
	 * A monotonically increasing number, denoting the row number in a repeatable view
	 *
	 * @var  int
	 */
	public $rowid;

	/**
	 * The item being rendered in a repeatable form field
	 *
	 * @var  DataModel
	 */
	public $item;

	/**
	 * Override for the list of client IDs. Comma separated list, e.g. "0,1"
	 *
	 * @var string
	 */
	public $client_ids = null;

	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 *
	 * @param   string  $name  The property name for which to the the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   2.1
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'static':
				if (empty($this->static))
				{
					$this->static = $this->getStatic();
				}

				return $this->static;
				break;

			case 'repeatable':
				if (empty($this->repeatable))
				{
					$this->repeatable = $this->getRepeatable();
				}

				return $this->repeatable;
				break;

			default:
				return parent::__get($name);
		}
	}

	/**
	 * Get the rendering of this field type for static display, e.g. in a single
	 * item view (typically a "read" task).
	 *
	 * @since 2.1
	 *
	 * @return  string  The field HTML
	 */
	public function getStatic()
	{
		$options = array(
			'id' => $this->id
		);

		return $this->getFieldContents($options);
	}

	/**
	 * Get the rendering of this field type for a repeatable (grid) display,
	 * e.g. in a view listing many item (typically a "browse" task)
	 *
	 * @since 2.1
	 *
	 * @return  string  The field HTML
	 */
	public function getRepeatable()
	{
		$options = array(
			'class' => $this->id
		);

		return $this->getFieldContents($options);
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @param   array   $fieldOptions  Options to be passed into the field
	 *
	 * @return  string  The field HTML
	 */
	public function getFieldContents(array $fieldOptions = array())
	{
		$id    = isset($fieldOptions['id']) ? 'id="' . $fieldOptions['id'] . '" ' : '';
		$class = $this->class . (isset($fieldOptions['class']) ? ' ' . $fieldOptions['class'] : '');

		return '<span ' . ($id ? $id : '') . 'class="' . $class . '">' .
			htmlspecialchars(GenericList::getOptionName($this->getOptions(), $this->value), ENT_COMPAT, 'UTF-8') .
			'</span>';
	}

	/**
	 * Get a list of all installed components and also translates them.
	 *
	 * The manifest_cache is used to get the extension names, since JInstaller is also
	 * translating those names in stead of the name column. Else some of the translations
	 * fails.
	 *
	 * @since    2.1
	 *
	 * @return 	array	An array of JHtml options.
	 */
	protected function getOptions()
	{
		$db = $this->form->getContainer()->platform->getDbo();

		// Check for client_ids override
		if ($this->client_ids !== null)
		{
			$client_ids = $this->client_ids;
		}
		else
		{
			$client_ids = $this->element['client_ids'];
		}

		$client_ids = explode(',', $client_ids);

		// Calculate client_ids where clause
		foreach ($client_ids as &$client_id)
		{
			$client_id = (int) trim($client_id);
			$client_id = $db->q($client_id);
		}

		$query = $db->getQuery(true)
			->select(
				array(
					$db->qn('name'),
					$db->qn('element'),
					$db->qn('client_id'),
					$db->qn('manifest_cache'),
				)
			)
			->from($db->qn('#__extensions'))
			->where($db->qn('type') . ' = ' . $db->q('component'))
			->where($db->qn('client_id') . ' IN (' . implode(',', $client_ids) . ')');
		$components = $db->setQuery($query)->loadObjectList('element');

		// Convert to array of objects, so we can use sortObjects()
		// Also translate component names with JText::_()
		$aComponents = array();
		$user = $this->form->getContainer()->platform->getUser();

		foreach ($components as $component)
		{
			// Don't show components in the list where the user doesn't have access for
			// TODO: perhaps add an option for this
			if (!$user->authorise('core.manage', $component->element))
			{
				continue;
			}

			$oData = (object) array(
				'value'	=> $component->element,
				'text' 	=> $this->translate($component, 'component')
			);
			$aComponents[$component->element] = $oData;
		}

		// Reorder the components array, because the alphabetical
		// ordering changed due to the JText::_() translation
		uasort(
			$aComponents,
			function ($a, $b) {
				return strcasecmp($a->text, $b->text);
			}
		);

		return $aComponents;
	}

	/**
	 * Translate a list of objects with JText::_().
	 *
	 * @param   \stdClass  $item  The component object
	 * @param   string     $type  The extension type (e.g. component)
	 *
	 * @since   2.1
	 *
	 * @return  string  $text  The translated name of the extension
	 *
	 * @see administrator/com_installer/models/extension.php
	 */
	public function translate($item, $type)
	{
        $platform = $this->form->getContainer()->platform;

		// Map the manifest cache to $item. This is needed to get the name from the
		// manifest_cache and NOT from the name column, else some JText::_() translations fails.
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

		$lang = $platform->getLanguage();

		switch ($type)
		{
			case 'component':
				$source = JPATH_ADMINISTRATOR . '/components/' . $item->element;
				$lang->load("$item->element.sys", JPATH_ADMINISTRATOR, null, false, false)
					||	$lang->load("$item->element.sys", $source, null, false, false)
					||	$lang->load("$item->element.sys", JPATH_ADMINISTRATOR, $lang->getDefault(), false, false)
					||	$lang->load("$item->element.sys", $source, $lang->getDefault(), false, false);
				break;
		}

		$text = JText::_($item->name);

		return $text;
	}
}
