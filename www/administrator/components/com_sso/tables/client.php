<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2020 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;

defined('_JEXEC') or die;

/**
 * Client table.
 *
 * @since  1.0.0
 */
class TableClient extends Table
{
	/**
	 * Constructor.
	 *
	 * @param   JDatabaseDriver  $db  A database connector object.
	 *
	 * @since   1.0.0
	 */
	public function __construct($db)
	{
		parent::__construct('#__sso_clients', 'id', $db);
	}
}
