<?php
/**
 * @package     SSO
 * @subpackage  Install
 *
 * @author      RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright   Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        https://rolandd.com
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;

/**
 * Script to run on installation of RO SSO package.
 *
 * @package  SSO
 * @since    1.0.0
 */
return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(
            InstallerScriptInterface::class,
            new class ($container->get(AdministratorApplication::class)
            )  extends InstallerScript {
                private AdministratorApplication $app;

                /**
                 * Set the minimum versions.
                 *
                 * @return  void
                 *
                 * @since   1.2.0
                 */
                public function __construct(AdministratorApplication $app)
                {
                    $this->app = $app;
                    $this->minimumPhp = '8.2';
                    $this->minimumJoomla = '5.0';
                }

                public function install(InstallerAdapter $adapter): bool
                {
                    $config = $this->app->getConfig();
                    $tmpPath = $config->get('tmp_path');
                    $this->app->enqueueMessage('Successfully installed RO Single Sign On Package', 'success');
                    $this->app->enqueueMessage('RO Single Sign On creates a backup of your configuration in the folder ' . $tmpPath . '/simplesamlphp Check that there is nothing left behind as it may contain sensitive data.', 'warning');

                    return true;
                }

                public function update(InstallerAdapter $adapter): bool
                {
                    $config = $this->app->getConfig();
                    $tmpPath = $config->get('tmp_path');
                    $this->app->enqueueMessage('Successfully updated RO Single Sign On Package', 'success');
                    $this->app->enqueueMessage('RO Single Sign On creates a backup of your configuration in the folder ' . $tmpPath . '/simplesamlphp Check that there is nothing left behind as it may contain sensitive data.', 'warning');

                    return true;
                }

                public function uninstall(InstallerAdapter $adapter): bool
                {
                    $this->app->enqueueMessage('Successfully uninstalled RO Single Sign On Package', 'success');

                    return true;
                }

                /**
                 * Called after any type of action
                 *
                 * @return  boolean  True on success
                 *
                 * @since   1.0.0
                 */
                public function preflight($type, $parent): bool
                {
                    switch ($type) {
                        case 'update':
                        case 'uninstall':
                            try {
                                $this->preflightUninstall();
                            } catch (Exception $exception) {
                                $this->app->enqueueMessage($exception->getMessage());
                                return false;
                            }
                            break;
                    }

                    return true;
                }

                /**
                 * Called after any type of action
                 *
                 * @param   string            $type    Which action is happening (install|uninstall|discover_install|update)
                 * @param   InstallerAdapter  $adapter  The object responsible for running this script
                 *
                 * @return  boolean  True on success
                 *
                 * @since   1.0.0
                 */
                public function postflight(string $type, InstallerAdapter $adapter): bool
                {
                    switch ($type) {
                        case 'update':
                        case 'uninstall':
                            $this->postflightUninstall();
                            break;
                    }

                    return true;
                }

                public function preflightUninstall(): void
                {
                    if (!file_exists(JPATH_LIBRARIES . '/simplesamlphp/'))
                    {
                        return;
                    }

                    $config = $this->app->getConfig();
                    $tmpPath = $config->get('tmp_path');
                    $filename = $tmpPath . '/simplesamlphp/.htaccess';
                    $content = "order allow,deny\ndeny from all";

                    if (!File::write($filename, $content)) {
                        throw new RuntimeException('Cannot write .htaccess file to folder ' . $filename . '. Cannot create a backup of the SimpleSAMLphp configuration.');
                    }

                    if (file_exists(JPATH_LIBRARIES . '/simplesamlphp/cert'))
                    {
                        Folder::copy(JPATH_LIBRARIES . '/simplesamlphp/cert', $tmpPath . '/simplesamlphp/cert', '', true);
                    }

                    if (file_exists(JPATH_LIBRARIES . '/simplesamlphp/config'))
                    {
                        Folder::copy(JPATH_LIBRARIES . '/simplesamlphp/config', $tmpPath . '/simplesamlphp/config', '', true);
                    }

                    if (file_exists(JPATH_LIBRARIES . '/simplesamlphp/metadata'))
                    {
                        Folder::copy(JPATH_LIBRARIES . '/simplesamlphp/metadata', $tmpPath . '/simplesamlphp/metadata', '', true);
                    }

                    if (file_exists(JPATH_LIBRARIES . '/simplesamlphp/metadata-generated'))
                    {
                        Folder::copy(JPATH_LIBRARIES . '/simplesamlphp/metadata-generated', $tmpPath . '/simplesamlphp/metadata-generated', '', true);
                    }
                }

                public function postflightUninstall(): void
                {
                    if (!file_exists(JPATH_LIBRARIES . '/simplesamlphp/'))
                    {
                        return;
                    }

                    $config = $this->app->getConfig();
                    $tmpPath = $config->get('tmp_path');

                    if (file_exists($tmpPath . '/simplesamlphp/cert'))
                    {
                        Folder::copy($tmpPath . '/simplesamlphp/cert', JPATH_LIBRARIES . '/simplesamlphp/cert', '', true);
                        Folder::delete($tmpPath . '/simplesamlphp/cert');
                    }

                    if (file_exists($tmpPath . '/simplesamlphp/config'))
                    {
                        Folder::copy($tmpPath . '/simplesamlphp/config', JPATH_LIBRARIES . '/simplesamlphp/config', '', true);
                        Folder::delete($tmpPath . '/simplesamlphp/config');
                    }

                    if (file_exists($tmpPath . '/simplesamlphp/metadata'))
                    {
                        Folder::copy($tmpPath . '/simplesamlphp/metadata', JPATH_LIBRARIES . '/simplesamlphp/metadata', '', true);
                        Folder::delete($tmpPath . '/simplesamlphp/metadata');
                    }

                    if (file_exists($tmpPath . '/simplesamlphp/metadata-generated'))
                    {
                        Folder::copy($tmpPath . '/simplesamlphp/metadata-generated', JPATH_LIBRARIES . '/simplesamlphp/metadata-generated', '', true);
                        Folder::delete($tmpPath . '/simplesamlphp/metadata-generated');
                    }

                    Folder::delete($tmpPath . '/simplesamlphp');
                }
            }
        );
    }
};
