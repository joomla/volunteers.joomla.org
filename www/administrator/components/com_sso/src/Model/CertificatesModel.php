<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Filesystem\Folder;

/**
 * Certificates model.
 *
 * @package  SSO.Component
 * @since    1.0.0
 */
class CertificatesModel extends BaseDatabaseModel
{
    public function getItems(): array
    {
        $files = Folder::files(PATH_SIMPLESAMLPHP . '/cert');

        $certificates = [];

        foreach ($files as $file)
        {
            $parts = pathinfo($file);

            if (!isset($certificates[$parts['filename']]))
            {
                $certificates[$parts['filename']] = [];
            }

            $ext = pathinfo($file, PATHINFO_EXTENSION);

            switch ($ext)
            {
                case 'crt':
                    $certificates[$parts['filename']]['crt'] = $file;
                    break;
                case 'pem':
                    $certificates[$parts['filename']]['pem'] = $file;
                    break;
            }
        }

        return $certificates;
    }
}
