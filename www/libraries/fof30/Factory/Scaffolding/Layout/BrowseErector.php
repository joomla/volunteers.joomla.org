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
 * Erects a scaffolding XML for browse views
 *
 * @package FOF30\Factory\Scaffolding
 * @deprecated 3.1  Support for XML forms will be removed in FOF 4
 */
class BrowseErector extends BaseErector implements ErectorInterface
{
	public function build()
	{
		// Get a reference to the model
		$model = $this->model;

		// Create the "no records" language string
		$noRowsKey = strtoupper($this->builder->getContainer()->componentName) . '_COMMON_NORECORDS';
		$this->addString($noRowsKey, 'There are no records to display');

		// Create the attributes of the form's base element
		$this->xml->addAttribute('type', 'browse');
		$this->xml->addAttribute('show_header', "1");
		$this->xml->addAttribute('show_filters', "1");
		$this->xml->addAttribute('show_pagination', "1");
		$this->xml->addAttribute('norows_placeholder', $noRowsKey);

		// Create the headerset and fieldset sections of the form file
		$headerSet = $this->xml->addChild('headerset');
		$fieldSet = $this->xml->addChild('fieldset');
		$fieldSet->addAttribute('name', 'items');

		// Get the database fields
		$allFields = $model->getTableFields();

		// Ordering must go first
		if ($model->hasField('ordering'))
		{
			$this->applyOrderingField($model, $headerSet, $fieldSet, $allFields);

		}

		// Primary key field goes next
		$this->applyPrimaryKeyField($model, $headerSet, $fieldSet, $allFields);

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
				$this->applyAccessLevelField($model, $headerSet, $fieldSet, $fieldName);

				continue;
			}

			// title => Title
			if ($model->getFieldAlias('title') == $fieldName)
			{
				$this->applyTitleField($model, $headerSet, $fieldSet, $fieldName);

				continue;
			}

			// slug => Hide if there is a title field as well
			if ($model->getFieldAlias('slug') == $fieldName)
			{
				$titleField = $model->getFieldAlias('title');

				if (array_key_exists($titleField, $allFields))
				{
					continue;
				}
			}

			// tag => Tag
			if ($model->getFieldAlias('tag') == $fieldName)
			{
				$this->applyTagField($model, $headerSet, $fieldSet, $fieldName);

				continue;
			}

			// enabled => Actions
			if ($model->getFieldAlias('enabled') == $fieldName)
			{
				$this->applyActionsField($model, $headerSet, $fieldSet, $fieldName);

				continue;
			}

			// cache_handler => CacheHandler
			if ($lowercaseFieldName == 'cache_handler')
			{
				$this->applyCacheHandlerField($model, $headerSet, $fieldSet, $fieldName);

				continue;
			}

			// component_id => Components
			if ($lowercaseFieldName == 'component_id')
			{
				$this->applyComponentsField($model, $headerSet, $fieldSet, $fieldName);

				continue;
			}

			// body, introtext, fulltext => Editor
			if (in_array($lowercaseFieldName, array('body', 'introtext', 'fulltext', 'description')))
			{
				$this->applyEditorField($model, $headerSet, $fieldSet, $fieldName);

				continue;
			}


			// email, *_email => Email
			if (($lowercaseFieldName == 'email') || (substr($lowercaseFieldName, -6) == 'email'))
			{
				$this->applyEmailField($model, $headerSet, $fieldSet, $fieldName);

				continue;
			}

			// image, media, *_image => Media
			if (
				in_array($lowercaseFieldName, array('image', 'media'))
				|| (substr($lowercaseFieldName, -6) == '_image')
			)
			{
				$this->applyMediaField($model, $headerSet, $fieldSet, $fieldName);

				continue;
			}

			// language, lang, lang_id => Language
			if (in_array($lowercaseFieldName, array('language', 'lang', 'lang_id')))
			{
				$this->applyLanguageField($model, $headerSet, $fieldSet, $fieldName);

				continue;
			}

			// password, passwd, pass => Password
			if (in_array($lowercaseFieldName, array('password', 'passwd', 'pass')))
			{
				$this->applyPasswordField($model, $headerSet, $fieldSet, $fieldName);

				continue;
			}

			// plugin_id => Plugins
			if ($lowercaseFieldName == 'plugin_id')
			{
				$this->applyPluginsField($model, $headerSet, $fieldSet, $fieldName);

				continue;
			}

			// asset_id => Rules (not applicable here)
			if ($lowercaseFieldName == 'asset_id')
			{
				continue;
			}

			// session_handler => SessionHandler
			if ($lowercaseFieldName == 'session_handler')
			{
				$this->applySessionHandlerField($model, $headerSet, $fieldSet, $fieldName);

				continue;
			}

			// tel, telephone, phone => Tel
			if (in_array($lowercaseFieldName, array('tel', 'telephone', 'phone')))
			{
				$this->applyTelField($model, $headerSet, $fieldSet, $fieldName);

				continue;
			}

			// timezone, tz, time_zone => Timezone
			if (in_array($lowercaseFieldName, array('timezone', 'tz', 'time_zone')))
			{
				$this->applyTimezoneField($model, $headerSet, $fieldSet, $fieldName);

				continue;
			}

			// url, link, href => Url
			if (in_array($lowercaseFieldName, array('url', 'link', 'href')))
			{
				$this->applyUrlField($model, $headerSet, $fieldSet, $fieldName);

				continue;
			}

			// user, user_id, userid, uid => User
			if (in_array($lowercaseFieldName, array('user', 'user_id', 'userid', 'uid')))
			{
				$this->applyUserField($model, $headerSet, $fieldSet, $fieldName);

				continue;
			}

			// group, group_id, groupid, gid => UserGroup
			if (in_array($lowercaseFieldName, array('group', 'group_id', 'groupid', 'gid')))
			{
				$this->applyUserGroupField($model, $headerSet, $fieldSet, $fieldName);

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
					$model->getRelations()->getRelation($foreignName);

					$this->applyRelationField($model, $headerSet, $fieldSet, $fieldName);

					continue;
				}
				catch (DataModel\Relation\Exception\RelationNotFound $e)
				{
					$foreignName = $this->model->getContainer()->inflector->pluralize($foreignName);

					try
					{
						$this->applyModelField($model, $headerSet, $fieldSet, $fieldName, $foreignName);

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
					$this->applyTextField($model, $headerSet, $fieldSet, $fieldName);
					break;

				case 'Editor':
					$this->applyEditorField($model, $headerSet, $fieldSet, $fieldName);
					break;

				case 'Calendar':
					$this->applyCalendarField($model, $headerSet, $fieldSet, $fieldName);
					break;

				case 'Checkbox':
					$this->applyCheckboxField($model, $headerSet, $fieldSet, $fieldName);
					break;

				case 'Integer':
					$this->applyIntegerField($model, $headerSet, $fieldSet, $fieldName);
					break;

				case 'Number':
					$this->applyNumberField($model, $headerSet, $fieldSet, $fieldName);
					break;

				case 'GenericList':
					$this->applyGenericListField($model, $headerSet, $fieldSet, $fieldName, $typeDef['params']);
					break;
			}
		}

		$this->pushResults();
	}

	/**
	 * Apply the ordering field
	 *
	 * @param \FOF30\Model\DataModel $model
	 * @param \SimpleXMLElement      $headerSet
	 * @param \SimpleXMLElement      $fieldSet
	 * @param array                  $allFields
	 */
	private function applyOrderingField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, array &$allFields)
	{
		$langDefs = $this->getFieldLabel('ordering');
		$this->addString($langDefs['label']['key'], $langDefs['label']['value']);
		$this->addString($langDefs['desc']['key'], $langDefs['desc']['value']);

		$fieldName = $model->getFieldAlias('ordering');

		$header = $headerSet->addChild('header');
		$header->addAttribute('name', $fieldName);
		$header->addAttribute('type', 'Ordering');
		$header->addAttribute('label', $langDefs['label']['key']);
		$header->addAttribute('sortable', 'true');
		$header->addAttribute('tdwidth', '1%');

		$field = $fieldSet->addChild('field');
		$field->addAttribute('name', $fieldName);
		$field->addAttribute('type', 'Ordering');
		$field->addAttribute('class', 'input-mini input-sm');

		unset($allFields[$fieldName]);
	}

	/**
	 * Apply the ordering field
	 *
	 * @param \FOF30\Model\DataModel $model
	 * @param \SimpleXMLElement      $headerSet
	 * @param \SimpleXMLElement      $fieldSet
	 * @param array                  $allFields
	 */
	private function applyPrimaryKeyField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, array &$allFields)
	{
		$keyField = $model->getKeyName();

		$langDefs = $this->getFieldLabel($keyField);
		$this->addString($langDefs['label']['key'], $langDefs['label']['value']);
		$this->addString($langDefs['desc']['key'], $langDefs['desc']['value']);

		$header = $headerSet->addChild('header');
		$header->addAttribute('name', $keyField);
		$header->addAttribute('type', 'RowSelect');
		$header->addAttribute('label', $langDefs['label']['key']);
		$header->addAttribute('sortable', 'true');
		$header->addAttribute('tdwidth', '20');

		$field = $fieldSet->addChild('field');
		$field->addAttribute('name', $keyField);
		$field->addAttribute('type', 'SelectRow');

		unset($allFields[$keyField]);
	}

	private function applyFieldOfType(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName, $fieldTypeHeader, $fieldTypeField, array $headerAttributes = array())
	{
		$langDefs = $this->getFieldLabel($fieldName);
		$this->addString($langDefs['label']['key'], $langDefs['label']['value']);
		$this->addString($langDefs['desc']['key'], $langDefs['desc']['value']);

		$header = $headerSet->addChild('header');
		$header->addAttribute('name', $fieldName);
		$header->addAttribute('type', $fieldTypeHeader);
		$header->addAttribute('label', $langDefs['label']['key']);

		if (!empty($headerAttributes))
		{
			foreach ($headerAttributes as $k => $v)
			{
				$header->addAttribute($k, $v);
			}
		}

		$field = $fieldSet->addChild('field');
		$field->addAttribute('name', $fieldName);
		$field->addAttribute('type', $fieldTypeField);
	}

	/**
	 * Apply an access level field
	 *
	 * @param \FOF30\Model\DataModel $model
	 * @param \SimpleXMLElement      $headerSet
	 * @param \SimpleXMLElement      $fieldSet
	 * @param string                 $fieldName
	 */
	private function applyAccessLevelField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'AccessLevel', 'AccessLevel', array(
			'sortable' => 'true'
		));
	}

	private function applyActionsField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Published', 'Actions', array(
			'sortable' => 'true'
		));
	}

	private function applyCacheHandlerField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Field', 'CacheHandler', array(
			'sortable' => 'true'
		));
	}

	private function applyCalendarField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Date', 'Calendar', array(
			'sortable' => 'true'
		));
	}

	private function applyCheckboxField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Field', 'Checkbox', array(
			'sortable' => 'true'
		));
	}

	private function applyComponentsField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Field', 'Components', array(
			'sortable' => 'true'
		));
	}

	private function applyEditorField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Searchable', 'Editor', array(
			'sortable' => 'true'
		));
	}

	private function applyEmailField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Searchable', 'Email', array(
			'sortable' => 'true'
		));
	}

	private function applyIntegerField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Searchable', 'Integer', array(
			'sortable' => 'true'
		));
	}

	private function applyNumberField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Searchable', 'Number', array(
			'sortable' => 'true'
		));
	}

	private function applyMediaField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Field', 'Media');
	}

	private function applyLanguageField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Language', 'Language', array(
			'sortable' => 'true'
		));
	}

	private function applyPasswordField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Searchable', 'Password', array(
			'sortable' => 'true'
		));
	}

	private function applyPluginsField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Field', 'Plugins', array(
			'sortable' => 'true'
		));
	}

	private function applySessionHandlerField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Field', 'SessionHandler', array(
			'sortable' => 'true'
		));
	}

	private function applyTelField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Searchable', 'Tel', array(
			'sortable' => 'true'
		));
	}

	private function applyTextField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Searchable', 'Text', array(
			'sortable' => 'true'
		));
	}

	private function applyTimezoneField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Field', 'Timezone', array(
			'sortable' => 'true'
		));
	}

	private function applyUrlField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Searchable', 'Url', array(
			'sortable' => 'true'
		));
	}

	private function applyUserField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Searchable', 'User', array(
			'sortable' => 'true'
		));
	}

	private function applyUserGroupField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Searchable', 'UserGroup', array(
			'sortable' => 'true'
		));
	}

	private function applyRelationField($model, $headerSet, $fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Field', 'Relation', array(
			'sortable' => 'true'
		));
	}

	private function applyTagField($model, $headerSet, $fieldSet, $fieldName)
	{
		$this->applyFieldOfType($model, $headerSet, $fieldSet, $fieldName, 'Field', 'Tag', array(
			'sortable' => 'true'
		));
	}

	private function applyTitleField($model, \SimpleXMLElement $headerSet, \SimpleXMLElement $fieldSet, $fieldName)
	{
		$langDefs = $this->getFieldLabel($fieldName);
		$this->addString($langDefs['label']['key'], $langDefs['label']['value']);
		$this->addString($langDefs['desc']['key'], $langDefs['desc']['value']);

		$header = $headerSet->addChild('header');
		$header->addAttribute('name', $fieldName);
		$header->addAttribute('type', 'Searchable');
		$header->addAttribute('label', $langDefs['label']['key']);

		if (!empty($headerAttributes))
		{
			foreach ($headerAttributes as $k => $v)
			{
				$header->addAttribute($k, $v);
			}
		}

		$field = $fieldSet->addChild('field');
		$field->addAttribute('name', $fieldName);
		$field->addAttribute('type', 'Sortable');
		$field->addAttribute('url', 'index.php?option=' .
			$this->builder->getContainer()->componentName . '&view=' . $this->model->getContainer()->inflector->singularize($this->viewName) . '&id=[ITEM:ID]&[TOKEN]=1'
		);
	}

	private function applyModelField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName, $modelName)
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

		$header = $headerSet->addChild('header');
		$header->addAttribute('name', $fieldName);
		$header->addAttribute('type', 'Model');
		$header->addAttribute('model', $modelName);
		$header->addAttribute('key_field', $foreignModel->getKeyName());
		$header->addAttribute('value_field', $value_field);
		$header->addAttribute('label', $langDefs['label']['key']);
		$header->addAttribute('sortable', 'true');

		$field = $fieldSet->addChild('field');
		$field->addAttribute('name', $fieldName);
		$field->addAttribute('type', 'Model');
		$field->addAttribute('model', $modelName);
		$field->addAttribute('key_field', $foreignModel->getKeyName());
		$field->addAttribute('value_field', $value_field);
	}

	private function applyGenericListField(DataModel $model, \SimpleXMLElement &$headerSet, \SimpleXMLElement &$fieldSet, $fieldName, $options)
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

		$header = $headerSet->addChild('header');
		$header->addAttribute('name', $fieldName);
		$header->addAttribute('type', 'Selectable');
		$header->addAttribute('label', $langDefs['label']['key']);
		$header->addAttribute('sortable', 'true');

		foreach ($displayOptions as $k => $v)
		{
			$header->addChild('option', $v)->addAttribute('value', $k);
		}

		$field = $fieldSet->addChild('field');
		$field->addAttribute('name', $fieldName);
		$field->addAttribute('type', 'GenericList');

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
