<?php

/**
 * @package    SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;

defined('_JEXEC') or die;

/**
 * SSO script file.
 *
 * The class cannot implement the InstallerScriptInterface because this will throw
 * a fatal error as the InstallerScript class has a different pre-flight signature
 *
 * @since       1.0.0
 */
return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(
            InstallerScriptInterface::class,
            new class (
                $container->get(AdministratorApplication::class),
                $container->get(DatabaseInterface::class)
            )  extends InstallerScript {
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
                    $this->app = $app;
                    $this->db = $db;
                    $this->minimumPhp = '8.2';
                    $this->minimumJoomla = '5.0';

                    if (!defined('PATH_SIMPLESAMLPHP'))
                    {
                        define('PATH_SIMPLESAMLPHP', JPATH_LIBRARIES . '/simplesamlphp');
                    }
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
                        case 'install':
                            $this->postflightInstall();
                            break;
                        case 'update':
                            $this->updateNameIdPolicy();
                            $this->updateMetadata();
                            $this->createSymlink();
                            break;
                        case 'uninstall':
                            $this->postflightUninstall();
                            break;
                    }

                    return true;
                }

                /**
                 * Convert a legacy string NameIDPolicy to the array format required
                 * by newer versions of SimpleSAMLphp.
                 *
                 * @return  void
                 *
                 * @since   __DEPLOY_VERSION__
                 */
                private function updateNameIdPolicy(): void
                {
                    $authSources = PATH_SIMPLESAMLPHP . '/config/authsources.php';

                    if (!file_exists($authSources)) {
                        return;
                    }

                    /** @var array $config */
                    require $authSources;

                    $modified = false;

                    foreach ($config as $alias => $settings) {
                        if (isset($settings['NameIDPolicy']) && is_string($settings['NameIDPolicy'])) {
                            $config[$alias]['NameIDPolicy'] = [
                                'Format'      => $settings['NameIDPolicy'],
                                'AllowCreate' => false,
                            ];
                            $modified = true;
                        }
                    }

                    if ($modified) {
                        file_put_contents($authSources, '<?php $config = ' . var_export($config, true) . ';');
                    }
                }

                private function updateMetadata(): void
                {
                    $authSources = PATH_SIMPLESAMLPHP . '/config/authsources.php';

                    if (!file_exists($authSources)) {
                        return;
                    }

                    /** @var array $config */
                    require $authSources;

                    $db    = $this->db;
                    $query = $db->createQuery()
                        ->select($db->quoteName(['id', 'metadata', 'alias']))
                        ->from($db->quoteName('#__sso_profiles'));
                    $db->setQuery($query);

                    $profiles = $db->loadObjectList();

                    $update = $db->createQuery()
                        ->update($db->quoteName('#__sso_profiles'));

                    foreach ($profiles as $profile) {
                        if (!empty($profile->metadata)
                            || !isset($config[$profile->alias])
                        ) {
                            continue;
                        }

                        $update
                            ->clear('set')
                            ->clear('where')
                            ->set(
                                $db->quoteName('metadata') . ' = ' . $db->quote(
                                    $config[$profile->alias]['idp']
                                )
                            )
                            ->where(
                                $db->quoteName('id') . ' = ' . $profile->id
                            );
                        $db->setQuery($update)
                            ->execute();
                    }
                }

                private function createSymlink(): void
                {
                    if (file_exists(JPATH_SITE . '/sso'))
                    {
                        return;
                    }

                    if (!symlink('libraries/simplesamlphp/public', JPATH_SITE . '/sso'))
                    {
                        $this->app->enqueueMessage('Unable to create symlink from ' . PATH_SIMPLESAMLPHP . '/public to ' . JPATH_SITE . '/sso', 'error');
                    }
                }

                private function postflightInstall(): void
                {
                    Folder::create(PATH_SIMPLESAMLPHP . '/metadata');
                    Folder::create(PATH_SIMPLESAMLPHP . '/metadata-generated');
                    Folder::create(PATH_SIMPLESAMLPHP . '/cert');

                    if (!file_exists(
                        PATH_SIMPLESAMLPHP . '/config/config.php'
                    )
                    ) {
                        File::copy(
                            PATH_SIMPLESAMLPHP . '/config/config.php.dist',
                            PATH_SIMPLESAMLPHP . '/config/config.php'
                        );
                    }

                    if (!file_exists(
                        PATH_SIMPLESAMLPHP . '/config/authsources.php'
                    )
                    ) {
                        File::copy(
                            PATH_SIMPLESAMLPHP . '/config/authsources.php.dist',
                            PATH_SIMPLESAMLPHP . '/config/authsources.php'
                        );
                    }

                    if (!file_exists(
                        PATH_SIMPLESAMLPHP . '/config/module_cron.php'
                    )
                    ) {
                        File::copy(
                            PATH_SIMPLESAMLPHP . '/modules/cron/config/module_cron.php.dist',
                            PATH_SIMPLESAMLPHP . '/config/module_cron.php'
                        );
                    }

                    if (!file_exists(
                        PATH_SIMPLESAMLPHP . '/config/module_metarefresh.php'
                    )
                    ) {
                        File::copy(
                            PATH_SIMPLESAMLPHP . '/modules/metarefresh/config-templates/module_metarefresh.php',
                            PATH_SIMPLESAMLPHP . '/config/module_metarefresh.php'
                        );
                    }

                    $this->createSymlink();
                }
                private function postflightUninstall(): void
                {
                    try {
                        if (is_link(JPATH_SITE . '/sso'))
                        {
                            unlink(JPATH_SITE . '/sso');
                        }

                        Folder::delete(PATH_SIMPLESAMLPHP);
                    } catch (Exception $exception) {
                        $this->app->enqueueMessage($exception->getMessage(), 'error');
                    }
                }
            }
        );
    }
};
