<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Ajax.reportspam
 *
 * @copyright   (C) 2016 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\Ajax\Reportspam\Extension;

use Joomla\CMS\Event\Plugin\AjaxEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Report a volunteer profile as spam
 *
 * @since  4.0.0
 */
final class Reportspam extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * Returns the events this plugin listens to.
     *
     * @return  array
     *
     * @since   4.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return ['onAjaxReportspam' => 'onAjaxReportspam'];
    }

    /**
     * Count a spam report for a volunteer, only logged in users with a valid form token can report
     *
     * @param   AjaxEvent  $event  The Ajax event
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function onAjaxReportspam(AjaxEvent $event): void
    {
        $app       = $this->getApplication();
        $volunteer = $app->getInput()->getInt('volunteer', 0);

        if (!$volunteer || $app->getIdentity()->guest || !Session::checkToken()) {
            $event->updateEventResult(false);

            return;
        }

        // +1 on the spam column of the volunteer
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__volunteers_volunteers'))
            ->set($db->quoteName('spam') . ' = ' . $db->quoteName('spam') . ' + 1')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $volunteer, ParameterType::INTEGER);

        $event->updateEventResult($db->setQuery($query)->execute());
    }
}
