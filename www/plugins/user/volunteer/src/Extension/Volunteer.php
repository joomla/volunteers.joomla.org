<?php

/**
 * @package         Joomla.Plugin
 * @subpackage      User.volunteer
 *
 * @copyright   (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\User\Volunteer\Extension;

use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Delete Volunteers Profile on User Delete
 *
 * @since  4.0.0
 */
final class Volunteer extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * Affects constructor behavior. If true, language files will be loaded automatically.
     *
     * @var    boolean
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     *
     * Remove corresponding volunteer data for deleted user
     *
     * @param   array    $user
     * @param   boolean  $success
     * @param   string   $msg
     *
     * @return  boolean
     *
     * @since 4.0.0
     */
    public function onUserAfterDelete(array $user, bool $success, string $msg): bool
    {
        if (!$success) {
            return false;
        }
        $db    = $this->getDatabase();
        $query = $db->getQuery(true);
        $query->delete($db->quoteName('#__volunteers_volunteers'));
        $query->where($db->quoteName('user_id') . ' = ' . $db->quote($user['id']));
        $db->setQuery($query);

        return $db->execute();
    }

    /**
     *
     * Checks to see if user being deleted was or is a Joomla! volunteer
     *
     * @param   array  $user
     *
     * @return bool
     *
     * @since 4.0.0
     */
    public function onUserBeforeDelete(array $user): bool
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
                          ->select($this->getDatabase()->quoteName('members.id'))
                          ->from($this->getDatabase()->quoteName('#__volunteers_members', 'members'))
                          ->leftJoin($this->getDatabase()->quoteName('#__volunteers_volunteers') . ' AS volunteers ON ' . $this->getDatabase()->quoteName('volunteers.id') . ' = ' . $this->getDatabase()->quoteName('members.volunteer'))
                          ->where($this->getDatabase()->quoteName('volunteers.user_id') . ' = ' . $this->getDatabase()->quote($user['id']));

        $volunteer = $db->setQuery($query)->loadResult();

        if ($volunteer) {
            $this->getApplication()->enqueueMessage(Text::sprintf('PLG_USER_VOLUNTEERS_CANNOT_DELETE', $user['name']), 'error');
            $this->getApplication()->redirect('index.php?option=com_users&view=users');
        }

        return true;
    }
    public static function getSubscribedEvents(): array
    {
        return ['onUserAfterDelete' => 'onUserAfterDelete', 'onUserBeforeDelete' => 'onUserBeforeDelete'];
    }
}
