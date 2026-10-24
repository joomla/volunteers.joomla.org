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
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Filesystem\Folder;
use Rolandd\Component\Sso\Administrator\Model\DatabaseModel;

defined('_JEXEC') or die;

/**
 * SSO script file.
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
            )  extends InstallerScript implements InstallerScriptInterface {
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

                    $this->deleteFolders = [
                        '/administrator/templates/atum/html/mod_login',
                        '/administrator/templates/atum/html/com_joomlaupdate',
                    ];

                    $this->deleteFiles = [
                        '/administrator/components/com_sso/models/forms/serviceprovider.xml',
                        '/administrator/components/com_sso/models/forms/fieldmap.xml',
                        '/administrator/components/com_sso/views/profile/tmpl/edit_serviceprovider.php',
                    ];
                }

                public function install(InstallerAdapter $adapter): bool
                {
                    $this->app->enqueueMessage('Successfully installed RO Single Sign On Component', 'success');

                    return true;
                }

                public function update(InstallerAdapter $adapter): bool
                {
                    $this->app->enqueueMessage('Successfully updated RO Single Sign On Component', 'success');

                    return true;
                }

                public function preflight($type, $parent): bool
                {
                   if ($type !== 'install')
                   {
                       return true;
                   }

                   if (!parent::preflight($type, $parent))
                   {
                       return false;
                   }

                    if (!class_exists('PDO')) {
                        Factory::getApplication()->enqueueMessage(
                            Text::_('COM_SSO_PDO_NOT_INSTALLED'),
                            'error'
                        );

                        return false;
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
                        case 'install':
                            $this->createNamespaceMap();
                            $this->verifyFileSystem($adapter);

                            try
                            {
                                $this->checkDatabase();
                            }
                            catch (Exception $exception)
                            {
                                $this->app->enqueueMessage($exception->getMessage());
                            }
                            break;
                    }

                    return true;
                }

                /**
                 * Called on uninstallation
                 *
                 * @param   InstallerAdapter  $adapter  The object responsible for running this script
                 *
                 * @return  bool
                 *
                 * @since   1.0.0
                 */
                public function uninstall(InstallerAdapter $adapter): bool
                {
                    $this->removeFiles();

                    return true;
                }

                private function verifyFileSystem(InstallerAdapter $adapter): void
                {
                    if (!file_exists(JPATH_LIBRARIES . '/simplesamlphp/cert'))
                    {
                        Folder::create(JPATH_LIBRARIES . '/simplesamlphp/cert');
                    }

                    $templateSource = $adapter->parent->getPath('source') . '/administrator/templates/atum/html';

                    if (!file_exists($templateSource))
                    {
                        return;
                    }

                    // Each override is copied independently so that adding a new override in a
                    // later release still gets deployed to sites that already have an older one
                    // (e.g. mod_login) in place.
                    $overrides = [
                        'mod_login',
                        'com_joomlaupdate',
                    ];

                    foreach ($overrides as $override)
                    {
                        $overrideSource = $templateSource . '/' . $override;
                        $overrideDestination = JPATH_ADMINISTRATOR . '/templates/atum/html/' . $override;

                        if (!file_exists($overrideDestination) && file_exists($overrideSource))
                        {
                            Folder::copy($overrideSource, $overrideDestination, '', true);
                        }
                    }
                }

                private function createNamespaceMap(): void
                {
                    // Borrowed from the script.ats.php file from Akeeba Ticket System
                    // Forcibly create the autoload_psr4.php file afresh.
                    if (class_exists(JNamespacePsr4Map::class))
                    {
                        try
                        {
                            $nsMap = new JNamespacePsr4Map();

                            @clearstatcache(JPATH_CACHE . '/autoload_psr4.php');

                            if (function_exists('opcache_invalidate'))
                            {
                                @opcache_invalidate(JPATH_CACHE . '/autoload_psr4.php');
                            }

                            @clearstatcache(JPATH_CACHE . '/autoload_psr4.php');
                            $nsMap->create();

                            if (function_exists('opcache_invalidate'))
                            {
                                @opcache_invalidate(JPATH_CACHE . '/autoload_psr4.php');
                            }

                            $nsMap->load();
                        }
                        catch (\Throwable $e)
                        {
                            // In case of failure, just try to delete the old autoload_psr4.php file
                            if (function_exists('opcache_invalidate'))
                            {
                                @opcache_invalidate(JPATH_CACHE . '/autoload_psr4.php');
                            }

                            @unlink(JPATH_CACHE . '/autoload_psr4.php');
                            @clearstatcache(JPATH_CACHE . '/autoload_psr4.php');

                            Factory::getApplication()->createExtensionNamespaceMap();
                        }
                    }
                }

                /**
                 * Check the database structure.
                 *
                 * @return  void
                 *
                 * @since   7.5.0
                 */
                private function checkDatabase(): void
                {
                    /** @var DatabaseModel $model */
                    $model = $this->app->bootComponent('com_sso')->getMVCFactory()->createModel('Database', 'Administrator', ['ignore_request' => true]);
                    $model->process();

                }
            }
        );
    }
};
