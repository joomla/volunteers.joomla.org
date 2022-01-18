<?php
/**
 * Akeeba Engine
 *
 * @package   akeebaengine
 * @copyright Copyright (c)2006-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Engine\Postproc;

defined('AKEEBAENGINE') || die();

class Cloudme extends Webdav
{
	public function __construct()
	{
		$this->settingsKey = 'cloudme';

		parent::__construct();
	}

	protected function modifySettings(array &$settings)
	{
		$settings['baseUri'] = 'https://webdav.cloudme.com/' . $settings['userName'] . '/CloudDrive/Documents/CloudMe';
	}
}
