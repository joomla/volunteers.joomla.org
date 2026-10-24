<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License version 3, or later
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

namespace Akeeba\Engine\Postproc\Connector;

defined('AKEEBAENGINE') || die();

use Akeeba\Engine\Postproc\Connector\Cloudfiles\Exception\Http;
use Akeeba\Engine\Postproc\Connector\Cloudfiles\Exception\Missing\Apikey;
use Akeeba\Engine\Postproc\Connector\Cloudfiles\Exception\Missing\Tenantid;
use Akeeba\Engine\Postproc\Connector\Cloudfiles\Exception\Missing\Username;
use Akeeba\Engine\Postproc\Connector\Cloudfiles\Request;
use DateTime;

class Ovh extends Swift
{
	/**
	 * Ovh constructor.
	 *
	 * @param   string  $tenantId  The OpenStack tenant ID.
	 * @param   string  $username  The OpenStack username.
	 * @param   string  $password  The OpenStack password.
	 *
	 * @throws  Apikey
	 * @throws  Tenantid
	 * @throws  Username
	 * @since   6.1.0
	 *
	 */
	public function __construct($tenantId, $username, $password)
	{
		// OVH is now using Keystone v3
		$authEndpoint = 'https://auth.cloud.ovh.net';

		// Data validation
		if (empty($tenantId))
		{
			throw new TenantId('You have not specified your OVH OpenStack Project ID');
		}

		if (empty($username))
		{
			throw new Username('You have not specified your OVH OpenStack Username');
		}

		if (empty($username))
		{
			throw new Apikey('You have not specified your OVH OpenStack Password');
		}

		parent::__construct('v3', $authEndpoint, $tenantId, $username, $password, 'Default');
	}
}
