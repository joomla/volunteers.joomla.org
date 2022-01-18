<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace FOF40\Utils;

defined('_JEXEC') || die;

use FOF40\Model\DataModel;

/**
 * Generate phpDoc type hints for the magic properties and methods of your DataModels.
 *
 * Usage:
 * $typeHinter = new ModelTypeHints($instanceOfYourFOFDataModel);
 * var_dump($typeHinter->getHints());
 *
 * This will dump the type hints you should add to the DocBlock of your DataModel class to allow IDEs such as phpStorm
 * to provide smart type hinting for magic property and method access.
 *
 * @package FOF40\Utils
 */
class ModelTypeHints
{
	/**
	 * The model for which to create type hints
	 *
	 * @var DataModel
	 */
	protected $model;

	/**
	 * Name of the class. If empty will be inferred from the current object
	 *
	 * @var string
	 */
	protected $className;

	/**
	 * Public constructor
	 *
	 * @param   \FOF40\Model\DataModel  $model  The model to create hints for
	 */
	public function __construct(DataModel $model)
	{
		$this->model     = $model;
		$this->className = get_class($model);
	}

	/**
	 * Translates the database field type into a PHP base type
	 *
	 * @param   string  $type  The type of the field
	 *
	 * @return  string  The PHP base type
	 */
	public static function getFieldType(string $type): string
	{
		// Remove parentheses, indicating field options / size (they don't matter in type detection)
		if (!empty($type))
		{
			[$type,] = explode('(', $type);
		}

		$detectedType = null;

		switch (trim($type))
		{
			case 'varchar':
			case 'text':
			case 'smalltext':
			case 'longtext':
			case 'char':
			case 'mediumtext':
			case 'character varying':
			case 'nvarchar':
			case 'nchar':
				$detectedType = 'string';
				break;

			case 'date':
			case 'datetime':
			case 'time':
			case 'year':
			case 'timestamp':
			case 'timestamp without time zone':
			case 'timestamp with time zone':
				$detectedType = 'string';
				break;

			case 'tinyint':
			case 'smallint':
				$detectedType = 'bool';
				break;

			case 'float':
			case 'currency':
			case 'single':
			case 'double':
				$detectedType = 'float';
				break;
		}

		// Sometimes we have character types followed by a space and some cruft. Let's handle them.
		if (is_null($detectedType) && !empty($type))
		{
			[$type,] = explode(' ', $type);

			switch (trim($type))
			{
				case 'varchar':
				case 'text':
				case 'smalltext':
				case 'longtext':
				case 'char':
				case 'mediumtext':
				case 'nvarchar':
				case 'nchar':
					$detectedType = 'string';
					break;

				case 'date':
				case 'datetime':
				case 'time':
				case 'year':
				case 'timestamp':
				case 'enum':
					$detectedType = 'string';
					break;

				case 'tinyint':
				case 'smallint':
					$detectedType = 'bool';
					break;

				case 'float':
				case 'currency':
				case 'single':
				case 'double':
					$detectedType = 'float';
					break;

				default:
					$detectedType = 'int';
					break;
			}
		}

		// If all else fails assume it's an int and hope for the best
		if (empty($detectedType))
		{
			$detectedType = 'int';
		}

		return $detectedType;
	}

	/**
	 * @param   string  $className
	 */
	public function setClassName(string $className): void
	{
		$this->className = $className;
	}

	/**
	 * Return the raw hints array
	 *
	 * @return  array
	 *
	 * @throws  \FOF40\Model\DataModel\Relation\Exception\RelationNotFound
	 */
	public function getRawHints(): array
	{
		$model = $this->model;

		$hints = [
			'property'      => [],
			'method'        => [],
			'property-read' => [],
		];

		$hasFilters = $model->getBehavioursDispatcher()->hasObserverClass('FOF40\Model\DataModel\Behaviour\Filters');

		$magicFields = [
			'enabled', 'ordering', 'created_on', 'created_by', 'modified_on', 'modified_by', 'locked_on', 'locked_by',
		];

		foreach ($model->getTableFields() as $fieldName => $fieldMeta)
		{
			$fieldType = static::getFieldType($fieldMeta->Type);

			if (!in_array($fieldName, $magicFields))
			{
				$hints['property'][] = [$fieldType, '$' . $fieldName];
			}

			if ($hasFilters)
			{
				$hints['method'][] = [
					'$this',
					$fieldName . '()',
					$fieldName . '(' . $fieldType . ' $v)',
				];
			}
		}

		$relations = $model->getRelations()->getRelationNames();

		$modelType      = get_class($model);
		$modelTypeParts = explode('\\', $modelType);
		array_pop($modelTypeParts);
		$modelType = implode('\\', $modelTypeParts) . '\\';

		if ($relations !== [])
		{
			foreach ($relations as $relationName)
			{
				$relationObject = $model->getRelations()->getRelation($relationName)->getForeignModel();
				$relationType   = get_class($relationObject);
				$relationType   = str_replace($modelType, '', $relationType);

				$hints['property-read'][] = [
					$relationType,
					'$' . $relationName,
				];
			}
		}

		return $hints;
	}

	/**
	 * Returns the docblock with the magic field hints for the model class
	 *
	 * @return  string
	 */
	public function getHints(): string
	{
		$modelName = $this->className;

		$text = "/**\n * Model $modelName\n *\n";

		$hints = $this->getRawHints();

		if (!empty($hints['property']))
		{
			$text .= " * Fields:\n *\n";

			$colWidth = 0;

			foreach ($hints['property'] as $hintLine)
			{
				$colWidth = max($colWidth, strlen($hintLine[0]));
			}

			$colWidth += 2;

			foreach ($hints['property'] as $hintLine)
			{
				$text .= " * @property  " . str_pad($hintLine[0], $colWidth, ' ') . $hintLine[1] . "\n";
			}

			$text .= " *\n";
		}

		if (!empty($hints['method']))
		{
			$text .= " * Filters:\n *\n";

			$colWidth  = 0;
			$col2Width = 0;

			foreach ($hints['method'] as $hintLine)
			{
				$colWidth  = max($colWidth, strlen($hintLine[0]));
				$col2Width = max($col2Width, strlen($hintLine[1]));
			}

			$colWidth  += 2;
			$col2Width += 2;

			foreach ($hints['method'] as $hintLine)
			{
				$text .= " * @method  " . str_pad($hintLine[0], $colWidth, ' ')
					. str_pad($hintLine[1], $col2Width, ' ')
					. $hintLine[2] . "\n";
			}

			$text .= " *\n";
		}

		if (!empty($hints['property-read']))
		{
			$text .= " * Relations:\n *\n";

			$colWidth = 0;

			foreach ($hints['property-read'] as $hintLine)
			{
				$colWidth = max($colWidth, strlen($hintLine[0]));
			}

			$colWidth += 2;

			foreach ($hints['property-read'] as $hintLine)
			{
				$text .= " * @property  " . str_pad($hintLine[0], $colWidth, ' ') . $hintLine[1] . "\n";
			}

			$text .= " *\n";
		}

		return $text . "**/\n";
	}
}
