<?php
/**
 * @package   FOF
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 2, or later
 */

namespace  FOF40\Model\DataModel\Filter;

defined('_JEXEC') || die;

class Relation extends Number
{
	/** @var \JDatabaseQuery The COUNT subquery to filter by */
	protected $subQuery;

	public function __construct($db, $relationName, $subQuery)
	{
		$field = (object)array(
			'name'	=> $relationName,
			'type'	=> 'relation',
		);

		parent::__construct($db, $field);

		$this->subQuery = $subQuery;
	}

	public function callback($value)
	{
		return call_user_func($value, $this->subQuery);
	}

	public function getFieldName()
	{
		return '(' . $this->subQuery . ')';
	}
}
