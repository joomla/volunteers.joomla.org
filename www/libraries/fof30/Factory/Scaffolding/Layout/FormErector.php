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
 * Erects a scaffolding XML for edit views
 *
 * @package FOF30\Factory\Scaffolding
 *
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class FormErector extends BaseErector implements ErectorInterface
{
	protected $addDescriptions = true;

	public function build()
	{
		// Get a reference to the model
		$model = $this->model;

		// Create the attributes of the form's base element
		$this->xml->addAttribute('validate', 'true');


		// Create the fieldset sections of the form file
		$labelKey = $this->getLangKeyPrefix() . 'GROUP_BASIC';
		$this->addString($labelKey, 'Basic');

		$fieldSet = $this->xml->addChild('fieldset');
		$fieldSet->addAttribute('name', 'scaffolding');
		$fieldSet->addAttribute('label', $labelKey);

		// Get the database fields
		$allFields = $model->getTableFields();

		// Ordering is not included
		if ($model->hasField('ordering'))
		{
			$fieldName = $model->getFieldAlias('ordering');
			unset($allFields[$fieldName]);
		}

		// Primary key is not inclided
		$primaryKeyField = $model->getKeyName();
		unset($allFields[$primaryKeyField]);

		// Get a list of "do not display" fields
		$doNotShow = $this->getDoNotShow();

		foreach ($allFields as $fieldName => $fieldDefinition)
		{
			// Skip the fields which shouldn't be displayed
			if (in_array($fieldName, $doNotShow))
			{
				continue;
			}

			// Get the lowercase field name and prepare to handle specially named fields
			$lowercaseFieldName = strtolower($fieldName);

			// access => AccessLevel
			if ($model->getFieldAlias('access') == $fieldName)
			{
				$this->applyAccessLevelField($model, $fieldSet, $fieldName);

				continue;
			}

			// tag => Tag
			if ($model->getFieldAlias('tag') == $fieldName)
			{
				$this->applyTagField($model, $fieldSet, $fieldName);

				continue;
			}

			// enabled => Published
			if ($model->getFieldAlias('enabled') == $fieldName)
			{
				$this->applyPublishedField($model, $fieldSet, $fieldName);

				continue;
			}

			// cache_handler => CacheHandler
			if ($lowercaseFieldName == 'cache_handler')
			{
				$this->applyCacheHandlerField($model, $fieldSet, $fieldName);

				continue;
			}

			// component_id => Components
			if ($lowercaseFieldName == 'component_id')
			{
				$this->applyComponentsField($model, $fieldSet, $fieldName);

				continue;
			}

			// body, introtext, fulltext, description => Editor
			if (in_array($lowercaseFieldName, array('body', 'introtext', 'fulltext', 'description')))
			{
				$this->applyEditorField($model, $fieldSet, $fieldName);

				continue;
			}


			// email, *_email => Email
			if (($lowercaseFieldName == 'email') || (substr($lowercaseFieldName, -6) == 'email'))
			{
				$this->applyEmailField($model, $fieldSet, $fieldName);

				continue;
			}

			// image, media, *_image => Media
			if (
				in_array($lowercaseFieldName, array('image', 'media'))
				|| (substr($lowercaseFieldName, -6) == '_image')
			)
			{
				$this->applyMediaField($model, $fieldSet, $fieldName);

				continue;
			}

			// language, lang, lang_id => Language
			if (in_array($lowercaseFieldName, array('language', 'lang', 'lang_id')))
			{
				$this->applyLanguageField($model, $fieldSet, $fieldName);

				continue;
			}

			// password, passwd, pass => Password
			if (in_array($lowercaseFieldName, array('password', 'passwd', 'pass')))
			{
				$this->applyPasswordField($model, $fieldSet, $fieldName);

				continue;
			}

			// plugin_id => Plugins
			if ($lowercaseFieldName == 'plugin_id')
			{
				$this->applyPluginsField($model, $fieldSet, $fieldName);

				continue;
			}

			// asset_id => Rules (new tab)
			if ($lowercaseFieldName == 'asset_id')
			{
				// Do not show the rules tab in read views
				if (!$this->addDescriptions)
				{
					continue;
				}

				$this->xml->addAttribute('tabbed', 1);
				$fieldSet->addAttribute('class', 'tab-pane active');
				$rulesSet = $this->xml->addChild('fieldset');

				$baseKey = $this->getLangKeyPrefix() . 'GROUP_PERMISSIONS';
				$this->addString($baseKey, 'Permissions');
				$this->addString($baseKey . '_DESC', 'Permissions for ' . $this->model->getContainer()->inflector->singularize($this->viewName));

				$rulesSet->addAttribute('name', 'rules');
				$rulesSet->addAttribute('class', 'tab-pane');
				$rulesSet->addAttribute('label', $baseKey);
				if ($this->addDescriptions)
				{
					$rulesSet->addAttribute('description', $baseKey . '_DESC');
				}

				$field = $rulesSet->addChild('field');
				$field->addAttribute('type', 'Hidden');
				$field->addAttribute('emptylabel', 'true');
				$field->addAttribute('filter', 'unset');
				$field->addAttribute('name', $model->getFieldAlias('asset_id'));

				$field = $rulesSet->addChild('field');
				$field->addAttribute('name', 'rules');
				$field->addAttribute('type', 'Rules');
				$field->addAttribute('emptylabel', 'true');
				$field->addAttribute('translate_label', 'false');
				$field->addAttribute('filter', 'rules');
				$field->addAttribute('validate', 'rules');
				$field->addAttribute('section', 'component');
				$field->addAttribute('component', $this->builder->getContainer()->componentName);

				continue;
			}

			// session_handler => SessionHandler
			if ($lowercaseFieldName == 'session_handler')
			{
				$this->applySessionHandlerField($model, $fieldSet, $fieldName);

				continue;
			}

			// tel, telephone, phone => Tel
			if (in_array($lowercaseFieldName, array('tel', 'telephone', 'phone')))
			{
				$this->applyTelField($model, $fieldSet, $fieldName);

				continue;
			}

			// timezone, tz, time_zone => Timezone
			if (in_array($lowercaseFieldName, array('timezone', 'tz', 'time_zone')))
			{
				$this->applyTimezoneField($model, $fieldSet, $fieldName);

				continue;
			}

			// url, link, href => Url
			if (in_array($lowercaseFieldName, array('url', 'link', 'href')))
			{
				$this->applyUrlField($model, $fieldSet, $fieldName);

				continue;
			}

			// user, user_id, userid, uid => User
			if (in_array($lowercaseFieldName, array('user', 'user_id', 'userid', 'uid')))
			{
				$this->applyUserField($model, $fieldSet, $fieldName);

				continue;
			}

			// group, group_id, groupid, gid => UserGroup
			if (in_array($lowercaseFieldName, array('group', 'group_id', 'groupid', 'gid')))
			{
				$this->applyUserGroupField($model, $fieldSet, $fieldName);

				continue;
			}

			// Special handling for myComponent_whatever_id fields
			$myComponentPrefix = $this->builder->getContainer()->bareComponentName . '_';

			if ((strpos($fieldName, $myComponentPrefix) === 0) && (substr($fieldName, -3) == '_id'))
			{
				$parts = explode('_', $fieldName);
				array_pop($parts);
				array_shift($parts);

				// myComponent_something_id => Relation or Model
				if (count($parts) == 1)
				{
					$foreignName = array_shift($parts);
				}
				// myComponent_something_another_id => Relation
				else
				{
					$foreignName1 = array_shift($parts);
					$foreignName1 = $this->model->getContainer()->inflector->pluralize($foreignName1);
					$foreignName2 = array_shift($parts);
					$foreignName2 = $this->model->getContainer()->inflector->pluralize($foreignName2);
					$modelName = $model->getName();
					$modelName = $this->model->getContainer()->inflector->pluralize($modelName);

					$foreignName = ($foreignName1 == $modelName) ? $foreignName2 : $foreignName1;
				}

				try
				{
					if (empty($parts))
					{
						throw new DataModel\Relation\Exception\RelationNotFound;
					}

					$model->getRelations()->getRelation($parts[0]);

					$this->applyRelationField($model, $fieldSet, $fieldName);

					continue;
				}
				catch (DataModel\Relation\Exception\RelationNotFound $e)
				{
					$foreignName = $this->model->getContainer()->inflector->pluralize($foreignName);

					try
					{
						$this->applyModelField($model, $fieldSet, $fieldName, $foreignName);

						continue;
					}
					catch (\Exception $e)
					{
					}
				}
			}

			// Other fields, use getFieldType
			$typeDef = $this->getFieldType($fieldDefinition->Type);
			switch ($typeDef['type'])
			{
				case 'Text':
					$this->applyTextField($model, $fieldSet, $fieldName);
					break;

				case 'Editor':
					$this->applyEditorField($model, $fieldSet, $fieldName);
					break;

				case 'Calendar':
					$this->applyCalendarField($model, $fieldSet, $fieldName);
					break;

				case 'Checkbox':
					$this->applyCheckboxField($model, $fieldSet, $fieldName);
					break;

				case 'Integer':
					$this->applyIntegerField($model, $fieldSet, $fieldName);
					break;

				case 'Number':
					$this->applyNumberField($model, $fieldSet, $fieldName);
					break;

				case 'GenericList':
					$this->applyGenericListField($model, $fieldSet, $fieldName, $typeDef['params']);
					break;
			}
		}

		$this->pushResults();
	}

	private function applyFieldOfType(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName, $fieldTypeField)
	{
		$langDefs = $this->getFieldLabel($fieldName);
		$this->addString($langDefs['label']['key'], $langDefs['label']['value']);
		$this->addString($langDefs['desc']['key'], $langDefs['desc']['value']);

		$field = $fieldSet->addChild('field');
		$field->addAttribute('name', $fieldName);
		$field->addAttribute('type', $fieldTypeField);
		$field->addAttribute('label', $langDefs['label']['key']);
		if ($this->addDescriptions)
		{
			$field->addAttribute('description', $langDefs['desc']['key']);
		}
	}

	/**
	 * Apply an access level field
	 *
	 * @param \FOF30\Model\DataModel $model
	 * @param \SimpleXMLElement      $headerSet
	 * @param \SimpleXMLElement      $fieldSet
	 * @param string                 $fieldName
	 */
	private function applyAccessLevelField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'AccessLevel');
	}

	private function applyPublishedField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'Published');
	}

	private function applyCacheHandlerField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'CacheHandler');
	}

	private function applyCalendarField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'Calendar');
	}

	private function applyCheckboxField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'Checkbox');
	}

	private function applyComponentsField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'Components');
	}

	private function applyEditorField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'Editor');
	}

	private function applyEmailField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'Email');
	}

	private function applyIntegerField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'Text');
	}

	private function applyNumberField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'Number');
	}

	private function applyMediaField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'Media');
	}

	private function applyLanguageField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'Language');
	}

	private function applyPasswordField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'Password');
	}

	private function applyPluginsField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'Plugins');
	}

	private function applySessionHandlerField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'SessionHandler');
	}

	private function applyTelField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'Tel');
	}

	private function applyTextField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'Text');
	}

	private function applyTimezoneField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'Timezone');
	}

	private function applyUrlField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'Url');
	}

	private function applyUserField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'User');
	}

	private function applyUserGroupField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'UserGroup');
	}

	private function applyRelationField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'Relation');
	}

	private function applyTagField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $fieldSet, $fieldName, 'Tag');
	}

	private function applyModelField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName, $modelName)
	{
		// This will fail if the model is invalid, e.g. we have example_foobar_id but no #__example_foobars table. The
		// error will balloon up the stack and the field will be rendered as simple number field instead of a Model
		// field.
		/** @var DataModel $foreignModel */
		$foreignModel = $model->getContainer()->factory->model($modelName);

		$value_field = $foreignModel->getKeyName();

		if ($foreignModel->hasField('title'))
		{
			$value_field = $foreignModel->getFieldAlias('title');
		}

		$langDefs = $this->getFieldLabel($fieldName);
		$this->addString($langDefs['label']['key'], $langDefs['label']['value']);
		$this->addString($langDefs['desc']['key'], $langDefs['desc']['value']);

		$field = $fieldSet->addChild('field');
		$field->addAttribute('name', $fieldName);
		$field->addAttribute('type', 'Model');
		$field->addAttribute('model', $modelName);
		$field->addAttribute('key_field', $foreignModel->getKeyName());
		$field->addAttribute('value_field', $value_field);
		$field->addAttribute('label', $langDefs['label']['key']);

		if ($this->addDescriptions)
		{
			$field->addAttribute('description', $langDefs['desc']['key']);
		}
	}

	private function applyGenericListField(DataModel $model, \SimpleXMLElement &$fieldSet, $fieldName, $options)
	{
		$displayOptions = array();

		foreach ($options as $k => $v)
		{
			$langKey = $this->builder->getContainer()->componentName . '_' . $this->viewName . '_' . $fieldName .
				'_OPT_' . $k;
			$this->addString($langKey, $v);
			$displayOptions[$k] = $langKey;
		}

		$langDefs = $this->getFieldLabel($fieldName);
		$this->addString($langDefs['label']['key'], $langDefs['label']['value']);
		$this->addString($langDefs['desc']['key'], $langDefs['desc']['value']);

		$field = $fieldSet->addChild('field');
		$field->addAttribute('name', $fieldName);
		$field->addAttribute('type', 'GenericList');
		$field->addAttribute('label', $langDefs['label']['key']);
		if ($this->addDescriptions)
		{
			$field->addAttribute('description', $langDefs['desc']['key']);
		}

		foreach ($displayOptions as $k => $v)
		{
			$field->addChild('option', $v)->addAttribute('value', $k);
		}
	}

	/**
	 * Create a list of fields which should not be shown in the form. These are fields like created/modified/locked
	 * user and time and other internal fields which should not be part of the form output.
	 *
	 * @return  array
	 */
	private function getDoNotShow()
	{
		$return = array();
		$checkFields = array('created_by', 'created_on', 'modified_by', 'modified_on', 'locked_by', 'locked_on');

		foreach ($checkFields as $checkField)
		{
			$return[] = $this->model->getFieldAlias($checkField);
		}

		return $return;
	}
}
