<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace FOF30\Factory\Magic;

use FOF30\Model\DataModel;
use FOF30\Factory\Exception\ModelNotFound;
use FOF30\Model\TreeModel;

defined('_JEXEC') or die;

/**
 * Creates a DataModel/TreeModel object instance based on the information provided by the fof.xml configuration file
 */
class ModelFactory extends BaseFactory
{
	/**
	 * Create a new object instance
	 *
	 * @param   string  $name    The name of the class we're making
	 * @param   array   $config  The config parameters which override the fof.xml information
	 *
	 * @return  TreeModel|DataModel  A new TreeModel or DataModel object
	 */
	public function make($name = null, array $config = array())
	{
		if (empty($name))
		{
			throw new ModelNotFound($name);
		}

		$appConfig = $this->container->appConfig;
		$name = ucfirst($name);

		$defaultConfig = array(
			'name'             => $name,
			'use_populate'     => $appConfig->get("models.$name.config.use_populate"),
			'ignore_request'   => $appConfig->get("models.$name.config.ignore_request"),
			'tableName'        => $appConfig->get("models.$name.config.tbl"),
			'idFieldName'      => $appConfig->get("models.$name.config.tbl_key"),
			'knownFields'      => $appConfig->get("models.$name.config.knownFields", null),
			'autoChecks'       => $appConfig->get("models.$name.config.autoChecks"),
			'contentType'      => $appConfig->get("models.$name.config.contentType"),
			'fieldsSkipChecks' => $appConfig->get("models.$name.config.fieldsSkipChecks", array()),
			'aliasFields'      => $appConfig->get("models.$name.field", array()),
			'behaviours'       => $appConfig->get("models.$name.behaviors", array()),
			'fillable_fields'  => $appConfig->get("models.$name.config.fillable_fields", array()),
			'guarded_fields'   => $appConfig->get("models.$name.config.guarded_fields", array()),
			'relations'        => $appConfig->get("models.$name.relations", array()),
		);

		$config = array_merge($defaultConfig, $config);

		// Get the default class names
		$dataModelClassName = $this->container->getNamespacePrefix($this->getSection()) . 'Model\\DefaultDataModel';

		if (!class_exists($dataModelClassName, true))
		{
			$dataModelClassName = '\\FOF30\\Model\\DataModel';
		}

		$treeModelClassName = $this->container->getNamespacePrefix($this->getSection()) . 'Model\\DefaultTreeModel';

		if (!class_exists($treeModelClassName, true))
		{
			$treeModelClassName = '\\FOF30\\Model\\TreeModel';
		}

		try
		{
			// First try creating a TreeModel
			$model = new $treeModelClassName($this->container, $config);
		}
		catch (DataModel\Exception\TreeIncompatibleTable $e)
		{
			// If the table isn't a nested set, create a regular DataModel
			$model = new $dataModelClassName($this->container, $config);
		}

		return $model;
	}
}
