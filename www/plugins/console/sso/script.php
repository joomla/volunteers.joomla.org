<?php
/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

defined('_JEXEC') or die;

return new class () implements ServiceProviderInterface {

	public function register(Container $container)
	{
		$container->set(
			InstallerScriptInterface::class,
			new class (
				$container->get(AdministratorApplication::class),
				$container->get(DatabaseInterface::class)
			) extends InstallerScript implements InstallerScriptInterface {
				private AdministratorApplication $app;
				private DatabaseInterface $db;

				/**
				 * Set the minimum versions.
				 *
				 * @return  void
				 *
				 * @since   1.2.0
				 */
				public function __construct(AdministratorApplication $app, DatabaseInterface $db)
				{
					$this->app           = $app;
					$this->db            = $db;
					$this->minimumPhp    = '[MINIMUM_PHP]';
					$this->minimumJoomla = '[MINIMUM_JOOMLA]';
				}

				public function install(InstallerAdapter $adapter): bool
				{
					$this->app->enqueueMessage(Text::_('PLG_CONSOLE_SSO_INSTALL_SUCCESS'), 'success');

					return true;
				}

				public function update(InstallerAdapter $adapter): bool
				{
					$this->app->enqueueMessage(Text::_('PLG_CONSOLE_SSO_UPDATE_SUCCESS'), 'success');

					return true;
				}

				/**
				 * Run the preflight operations.
				 *
				 * @param   string            $type    The parent class.
				 * @param   InstallerAdapter  $parent  The parent class.
				 *
				 * @return  bool  True on success | False on failure.
				 *
				 * @since   4.7.0
				 */
				public function preflight($type, $parent): bool
				{
					return true;
				}

				/**
				 * Run the postflight operations.
				 *
				 * @param   string            $type
				 * @param   InstallerAdapter  $adapter
				 *
				 * @return  bool True on success | False on failure.
				 *
				 * @since   2.0
				 */
				public function postflight(string $type, InstallerAdapter $adapter): bool
				{
					if ($type === 'install')
					{
						try
						{
							$this->postInstall();
						}
						catch (Exception $exception)
						{
							$this->app->enqueueMessage($exception->getMessage(), 'error');

							return false;
						}
					}

					return true;
				}

				/**
				 * Cleanup after uninstallation.
				 *
				 * @param   InstallerAdapter  $adapter
				 *
				 * @return  bool
				 *
				 * @since   2.0
				 */
				public function uninstall(InstallerAdapter $adapter): bool
				{
					return true;
				}

				private function postInstall(): void
				{
					$app = $this->app;
					$db  = $this->db;

					$query = $db->createQuery()
						->update($db->quoteName('#__extensions'))
						->set($db->quoteName('enabled') . ' =  1')
						->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
						->where($db->quoteName('element') . ' = ' . $db->quote('sso'))
						->where($db->quoteName('folder') . ' = ' . $db->quote('console'));

					$db->setQuery($query);

					try
					{
						$db->execute();
					}
					catch (Exception $exception)
					{
						$app->enqueueMessage(Text::sprintf('PLG_CONSOLE_SSO_PLUGIN_NOT_ENABLED', $exception->getMessage()), 'error');

						throw new RuntimeException($exception->getMessage());
					}

					$app->enqueueMessage(Text::_('PLG_CONSOLE_SSO_PLUGIN_ENABLED'));
				}
			}
		);
	}
};
