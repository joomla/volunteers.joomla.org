<?php
/**
 * @package     SSO.Component
 *
 * @author     RolandD Cyber Produksi <contact@rolandd.com>
 * @copyright  Copyright (C) 2017 - 2026 RolandD Cyber Produksi. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link       https://rolandd.com
 */

namespace Rolandd\Component\Sso\Administrator\Controller;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Rolandd\Component\Sso\Administrator\Model\DatabaseModel;

/**
 * SSO Controller.
 *
 * @package  SSO.Component.Component
 * @since    1.0.0
 */
class SsoController extends AdminController
{
    /**
     * Verifies the database structure.
     *
     * @return  void
     *
     * @since   2.3.0
     */
    public function verifyDatabase(): void
    {
        /** @var DatabaseModel $model */
        $model = $this->getModel('Database', 'Administrator');

        try
        {
            $model->process();

            $message = Text::_('COM_SSO_DATABASE_RESET');
            $type = 'message';
        }
        catch (Exception $exception)
        {
            $message = $exception->getMessage();
            $type = 'error';
        }

        $this->setRedirect(Route::_('index.php?option=com_sso&view=sso', false), $message, $type);
    }
}
