<?php
/**
 * Joomla.org site template
 *
 * @copyright   Copyright (C) 2005 - 2023 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Language\Text;

/**
 * Installation class to perform additional changes during install/uninstall/update
 *
 * @since  2.0
 * @note   This class name collides with the Joomla core installer script class, if we start hitting issues this class goes away
 */
class JoomlaInstallerScript extends InstallerScript
{
	/**
	 * Extension script constructor.
	 *
	 * @since   2.0
	 */
	public function __construct()
	{
		$this->minimumJoomla = '3.8';
		$this->minimumPhp    = '5.4';

		// Workaround for https://github.com/joomla/joomla-cms/issues/23219 by allowing downgrades!?
		$this->allowDowngrades = true;

		$this->deleteFiles = [
			'/language/en-GB/en-GB.tpl_joomla.ini',
			'/language/en-GB/en-GB.tpl_joomla.sys.ini',
			'/templates/joomla/images/apple-touch-icon-114-precomposed.png',
			'/templates/joomla/images/apple-touch-icon-144-precomposed.png',
			'/templates/joomla/images/apple-touch-icon-57-precomposed.png',
			'/templates/joomla/images/apple-touch-icon-72-precomposed.png',
			'/templates/joomla/html/pagination.php',
			'/templates/joomla/html/layouts/joomla/system/message.php',
			'/templates/joomla/html/layouts/joomla/pagination/links.php',
			'/templates/joomla/html/layouts/joomla/pagination/list.php',
			'/templates/joomla/css/bs3-polyfill.css',
			'/templates/joomla/fonts/icomoon-joomla.eot',
			'/templates/joomla/fonts/icomoon-joomla.svg',
			'/templates/joomla/fonts/icomoon-joomla.ttf',
			'/templates/joomla/fonts/icomoon-joomla.woff',
			'/templates/joomla/fonts/lte-ie7.js',
			'/templates/joomla/img/glyphicons-halflings-white.png',
			'/templates/joomla/img/glyphicons-halflings.png',
			'/templates/joomla/less/buttons.less',
			'/templates/joomla/less/icomoon-joomla.less',
			'/templates/joomla/less/rtl/alerts.less',
			'/templates/joomla/less/rtl/button-groups.less',
			'/templates/joomla/less/rtl/close.less',
			'/templates/joomla/less/rtl/dropdowns.less',
			'/templates/joomla/less/rtl/forms.less',
			'/templates/joomla/less/rtl/grid.less',
			'/templates/joomla/less/rtl/media.less',
			'/templates/joomla/less/rtl/mixins.less',
			'/templates/joomla/less/rtl/navbar.less',
			'/templates/joomla/less/rtl/navs.less',
			'/templates/joomla/less/rtl/pager.less',
			'/templates/joomla/less/rtl/pagination.less',
			'/templates/joomla/less/rtl/popovers.less',
			'/templates/joomla/less/rtl/responsive-1200px-min.less',
			'/templates/joomla/less/rtl/responsive-768px-979px.less',
			'/templates/joomla/less/rtl/responsive-navbar.less',
			'/templates/joomla/less/rtl/tables.less',
			'/templates/joomla/less/rtl/thumbnails.less',
			'/templates/joomla/less/rtl/tooltip.less',
			'/templates/joomla/less/rtl/type.less',
			'/templates/joomla/less/rtl/utilities.less',
			'/templates/joomla/less/template-rtl.less',
			'/templates/joomla/less/template.less',
			'/templates/joomla/less/variables.less',
			'/templates/joomla/css/template.css',
			'/templates/joomla/css/template.css.map',
			'/templates/joomla/css/template.min.css',
			'/templates/joomla/css/template.min.css.map',
			'/templates/joomla/css/template-rtl.css',
			'/templates/joomla/css/template-rtl.css.map',
			'/templates/joomla/css/template-rtl.min.css',
			'/templates/joomla/css/template-rtl.min.css.map',
			'/templates/joomla/js/blockadblock.js',
			'/templates/joomla/js/js.cookie.js',
			'/templates/joomla/js/template.js',
		];

		$this->deleteFolders = [
			'/templates/joomla/fonts',
			'/templates/joomla/img',
			'/templates/joomla/layouts/joomla/system',
			/**
			 * Whilst the CSS folder is no longer shipped as part of this template we leave it as nearly all core sites
			 * have a custom.css file which we do not want to delete! So don't delete it here
			 */
			// '/templates/joomla/css',
			'/templates/joomla/js',
			'/templates/joomla/less',
			'/templates/joomla/html/layouts/joomla/system',
		];
	}

	/**
	 * Ensure the template is made inheritable so CSS & JS can be found.
	 *
	 * @return  void
	 *
	 * @since   4.0.4
	 */
	protected function fixTemplateMode($name)
	{
		$db = Factory::getDbo();

		$clientId = 0;
		$query = $db->getQuery(true)
			->update($db->quoteName('#__template_styles'))
			->set($db->quoteName('inheritable') . ' = 1')
			->where($db->quoteName('template') . ' = ' . $db->quote($name))
			->where($db->quoteName('client_id') . ' = ' . $clientId);

		try
		{
			$db->setQuery($query)->execute();
		}
		catch (Exception $e)
		{
			echo Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()) . '<br>';

			return;
		}
	}

	/**
	 * Function to perform changes during postflight
	 *
	 * @param   string                                         $type    The action being performed
	 * @param   \Joomla\CMS\Installer\Adapter\TemplateAdapter  $parent  The class calling this method
	 *
	 * @return  void
	 *
	 * @since   2.0.1
	 */
	public function postflight($type, $parent)
	{
		$this->removeFiles();
        $this->fixTemplateMode('joomla');
	}
}
