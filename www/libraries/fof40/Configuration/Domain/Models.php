<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace FOF40\Configuration\Domain;

use SimpleXMLElement;

defined('_JEXEC') || die;

/**
 * Configuration parser for the models-specific settings
 *
 * @since    2.1
 */
class Models implements DomainInterface
{
	/**
	 * Parse the XML data, adding them to the $ret array
	 *
	 * @param   SimpleXMLElement  $xml  The XML data of the component's configuration area
	 * @param   array            &$ret  The parsed data, in the form of a hash array
	 *
	 * @return  void
	 */
	public function parseDomain(SimpleXMLElement $xml, array &$ret): void
	{
		// Initialise
		$ret['models'] = [];

		// Parse model configuration
		$modelsData = $xml->xpath('model');

		// Sanity check
		if (empty($modelsData))
		{
			return;
		}

		foreach ($modelsData as $aModel)
		{
			$key = (string) $aModel['name'];

			$ret['models'][$key]['behaviors']      = [];
			$ret['models'][$key]['behaviorsMerge'] = false;
			$ret['models'][$key]['tablealias']     = $aModel->xpath('tablealias');
			$ret['models'][$key]['fields']         = [];
			$ret['models'][$key]['relations']      = [];
			$ret['models'][$key]['config']         = [];


			// Parse configuration
			$optionData = $aModel->xpath('config/option');

			foreach ($optionData as $option)
			{
				$k                                 = (string) $option['name'];
				$ret['models'][$key]['config'][$k] = (string) $option;
			}

			// Parse field aliases
			$fieldData = $aModel->xpath('field');

			foreach ($fieldData as $field)
			{
				$k                                 = (string) $field['name'];
				$ret['models'][$key]['fields'][$k] = (string) $field;
			}

			// Parse behaviours
			$behaviorsData  = (string) $aModel->behaviors;
			$behaviorsMerge = (string) $aModel->behaviors['merge'];

			if (!empty($behaviorsMerge))
			{
				$behaviorsMerge = trim($behaviorsMerge);
				$behaviorsMerge = strtoupper($behaviorsMerge);

				if (in_array($behaviorsMerge, ['1', 'YES', 'ON', 'TRUE']))
				{
					$ret['models'][$key]['behaviorsMerge'] = true;
				}
			}

			if (!empty($behaviorsData))
			{
				$behaviorsData = explode(',', $behaviorsData);

				foreach ($behaviorsData as $behavior)
				{
					$behavior = trim($behavior);

					if (empty($behavior))
					{
						continue;
					}

					$ret['models'][$key]['behaviors'][] = $behavior;
				}
			}

			// Parse relations
			$relationsData = $aModel->xpath('relation');

			foreach ($relationsData as $relationData)
			{
				$type     = (string) $relationData['type'];
				$itemName = (string) $relationData['name'];

				if (empty($type) || empty($itemName))
				{
					continue;
				}

				$modelClass    = (string) $relationData['foreignModelClass'];
				$localKey      = (string) $relationData['localKey'];
				$foreignKey    = (string) $relationData['foreignKey'];
				$pivotTable    = (string) $relationData['pivotTable'];
				$ourPivotKey   = (string) $relationData['pivotLocalKey'];
				$theirPivotKey = (string) $relationData['pivotForeignKey'];

				$relation = [
					'type'              => $type,
					'itemName'          => $itemName,
					'foreignModelClass' => empty($modelClass) ? null : $modelClass,
					'localKey'          => empty($localKey) ? null : $localKey,
					'foreignKey'        => empty($foreignKey) ? null : $foreignKey,
				];

				if (!empty($ourPivotKey) || !empty($theirPivotKey) || !empty($pivotTable))
				{
					$relation['pivotLocalKey']   = empty($ourPivotKey) ? null : $ourPivotKey;
					$relation['pivotForeignKey'] = empty($theirPivotKey) ? null : $theirPivotKey;
					$relation['pivotTable']      = empty($pivotTable) ? null : $pivotTable;
				}

				$ret['models'][$key]['relations'][] = $relation;
			}
		}
	}

	/**
	 * Return a configuration variable
	 *
	 * @param   string &$configuration  Configuration variables (hashed array)
	 * @param   string  $var            The variable we want to fetch
	 * @param   mixed   $default        Default value
	 *
	 * @return  mixed  The variable's value
	 */
	public function get(array &$configuration, string $var, $default = null)
	{
		$parts = explode('.', $var);

		$view   = $parts[0];
		$method = 'get' . ucfirst($parts[1]);

		if (!method_exists($this, $method))
		{
			return $default;
		}

		array_shift($parts);
		array_shift($parts);

		return $this->$method($view, $configuration, $parts, $default);
	}

	/**
	 * Internal method to return the magic field mapping
	 *
	 * @param   string             $model          The model for which we will be fetching a field map
	 * @param   array  &           $configuration  The configuration parameters hash array
	 * @param   array              $params         Extra options
	 * @param   string|array|null  $default        Default magic field mapping; empty if not defined
	 *
	 * @return  string|array|null   Field map
	 */
	protected function getField(string $model, array &$configuration, array $params, $default = '')
	{
		$fieldmap = [];

		if (isset($configuration['models']['*']) && isset($configuration['models']['*']['fields']))
		{
			$fieldmap = $configuration['models']['*']['fields'];
		}

		if (isset($configuration['models'][$model]) && isset($configuration['models'][$model]['fields']))
		{
			$fieldmap = array_merge($fieldmap, $configuration['models'][$model]['fields']);
		}

		$map = $default;

		if (empty($params[0]) || ($params[0] == '*'))
		{
			$map = $fieldmap;
		}
		elseif (isset($fieldmap[$params[0]]))
		{
			$map = $fieldmap[$params[0]];
		}

		return $map;
	}

	/**
	 * Internal method to get model alias
	 *
	 * @param   string       $model          The model for which we will be fetching table alias
	 * @param   array        $configuration  [IN/OUT] The configuration parameters hash array
	 * @param   array        $params         Ignored
	 * @param   string|null  $default        Default table alias
	 *
	 * @return  string|null  Table alias
	 */
	protected function getTablealias(string $model, array &$configuration, array $params = [], ?string $default = null): ?string
	{
		$tableMap = [];

		if (isset($configuration['models']['*']['tablealias']))
		{
			$tableMap = $configuration['models']['*']['tablealias'];
		}

		if (isset($configuration['models'][$model]['tablealias']))
		{
			$tableMap = array_merge($tableMap, $configuration['models'][$model]['tablealias']);
		}

		if (empty($tableMap))
		{
			return null;
		}

		return $tableMap[0];
	}

	/**
	 * Internal method to get model behaviours
	 *
	 * @param   string      $model          The model for which we will be fetching behaviours
	 * @param   array  &    $configuration  The configuration parameters hash array
	 * @param   array       $params         Unused
	 * @param   array|null  $default        Default behaviour
	 *
	 * @return  array|null  Model behaviours
	 */
	protected function getBehaviors(string $model, array &$configuration, array $params = [], ?array $default = []): ?array
	{
		$behaviors = $default;

		if (isset($configuration['models']['*'])
			&& isset($configuration['models']['*']['behaviors'])
		)
		{
			$behaviors = $configuration['models']['*']['behaviors'];
		}

		if (isset($configuration['models'][$model])
			&& isset($configuration['models'][$model]['behaviors'])
		)
		{
			$merge = false;

			if (isset($configuration['models'][$model])
				&& isset($configuration['models'][$model]['behaviorsMerge'])
			)
			{
				$merge = (bool) $configuration['models'][$model]['behaviorsMerge'];
			}

			if ($merge)
			{
				$behaviors = array_merge($behaviors, $configuration['models'][$model]['behaviors']);
			}
			else
			{
				$behaviors = $configuration['models'][$model]['behaviors'];
			}
		}

		return $behaviors;
	}

	/**
	 * Internal method to get model relations
	 *
	 * @param   string      $model          The model for which we will be fetching relations
	 * @param   array  &    $configuration  The configuration parameters hash array
	 * @param   array       $params         Unused
	 * @param   array|null  $default        Default relations
	 *
	 * @return  array|null   Model relations
	 */
	protected function getRelations(string $model, array &$configuration, array $params = [], ?array $default = []): ?array
	{
		$relations = $default;

		if (isset($configuration['models']['*'])
			&& isset($configuration['models']['*']['relations'])
		)
		{
			$relations = $configuration['models']['*']['relations'];
		}

		if (isset($configuration['models'][$model])
			&& isset($configuration['models'][$model]['relations'])
		)
		{
			$relations = $configuration['models'][$model]['relations'];
		}

		return $relations;
	}

	/**
	 * Internal method to return the a configuration option for the Model.
	 *
	 * @param   string             $model          The view for which we will be fetching a task map
	 * @param   array  &           $configuration  The configuration parameters hash array
	 * @param   array              $params         Extra options; key 0 defines the option variable we want to fetch
	 * @param   string|array|null  $default        Default option; null if not defined
	 *
	 * @return  string|array|null  The setting for the requested option
	 */
	protected function getConfig(string $model, array &$configuration, array $params = [], $default = null)
	{
		$ret = $default;

		$config = [];

		if (isset($configuration['models']['*']['config']))
		{
			$config = $configuration['models']['*']['config'];
		}

		if (isset($configuration['models'][$model]['config']))
		{
			$config = array_merge($config, $configuration['models'][$model]['config']);
		}

		if (empty($params) || empty($params[0]))
		{
			return $config;
		}

		if (isset($config[$params[0]]))
		{
			$ret = $config[$params[0]];
		}

		return $ret;
	}
}
