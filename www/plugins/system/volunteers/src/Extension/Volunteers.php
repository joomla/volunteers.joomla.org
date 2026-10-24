<?php

/**
 * @package         Joomla.Plugins
 * @subpackage      System.actionlogs
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\Volunteers\Extension;

use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Volunteers\Administrator\Extension\VolunteersComponent;
use Joomla\Component\Volunteers\Administrator\Model\VolunteerModel;


/**
 * Joomla Identity Plugin class
 *
 * @since  4.0.0
 */
final class Volunteers extends CMSPlugin implements SubscriberInterface
{
    /**
     * If the user is not logged in, redirect to the login component
     *
     * @return bool
     * @since 4.0.0
     * @throws Exception
     */
    public function onAfterRoute(): bool
    {
        $app = $this->getApplication();

        // Run on frontend only
        if (!$app->isClient('site')) {
            return true;
        }

        // Get variables
        $input  = $app->getInput();
        $option = $input->getString('option');
        $view   = $input->getString('view');
        $id     = $input->getInt('id');

        // Check if volunteer url is correct
        if ($option == 'com_volunteers' && $view == 'volunteer') {
            $itemURL    = Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $id);
            $correctURL = Uri::getInstance()->toString(['scheme', 'host', 'port']) . $itemURL;
            $currentURL = str_replace("&", "&amp;", Uri::getInstance()->toString());
            if ($correctURL != $currentURL) {
                $app->redirect(Route::_('index.php?option=com_volunteers&view=volunteer&id=' . $id, false), 301);
            }
        }

        // Check if this is the volunteers own profile
        if ($option == 'com_volunteers' && $view == 'volunteer') {
            /** @var VolunteersComponent $extension */
            $extension   = $app->bootComponent('com_volunteers');
            /** @var VolunteerModel $model */
            $model       = $extension->getMVCFactory()->createModel('Volunteer', 'Administrator', ['ignore_request' => true]);
            $userId      = $app->getIdentity()->id;
            $volunteerId = (int) $model->getVolunteerId($userId);

            // Change active menu for own profile
            if ($volunteerId == $id) {
                $menu     = $app->getMenu();
                $menuItem = $menu->getItems('link', 'index.php?option=com_volunteers&view=my', true);
                $menu->setActive($menuItem->id);
            }
        }

        return true;
    }
    public static function getSubscribedEvents(): array
    {
        return ['onAfterRoute' => 'onAfterRoute'];
    }
}
