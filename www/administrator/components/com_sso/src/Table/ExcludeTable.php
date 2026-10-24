<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\Table;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

defined('_JEXEC') or die;

/**
 * Excludes table.
 *
 * @since  1.5.0
 */
class ExcludeTable extends Table
{
	/**
	 * Constructor.
	 *
	 * @param   DatabaseDriver  $db  A database connector object.
	 *
	 * @since   1.5.0
	 */
	public function __construct(DatabaseDriver $db)
	{
		parent::__construct('#__sso_excludes', 'id', $db);
	}

	/**
	 * Overrides Table::store to set modified data and user id.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.5.0
	 */
	public function store($updateNulls = false)
	{
		$date = Factory::getDate();
		$user = Factory::getUser();

		$this->set('modified', $date->toSql());
		$this->set('modified_by', $user->get('id'));

		if (!$this->get('id', false))
		{
			$this->set('created', $date->toSql());

			if (empty($this->get('created_by')))
			{
				$this->set('created_by', $user->get('id'));
			}
		}

		return parent::store($updateNulls);
	}
}
