<?php

/**
 * @version    4.0.0
 * @package    Com_Volunteers
 * @author     The Joomla Project <secretary@opensourcematters.org>
 * @copyright  2023 The Joomla Project
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Volunteers\Administrator\Field;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\User\UserFactoryInterface;

/**
 * Supports an HTML select list of categories
 *
 * @since  4.0.0
 */
class CreatedbyField extends FormField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  4.0.0
     */
    protected $type = 'createdby';

    /**
     * Method to get the field input markup.
     *
     * @return  string    The field input markup.
     *
     * @since   4.0.0
     * @throws Exception
     */
    protected function getInput()
    {
        // Initialize variables.
        $html = [];

        // Load user
        $user_id = $this->value;

        if ($user_id) {
            $container   = Factory::getContainer();
            $userFactory = $container->get(UserFactoryInterface::class);
            $user        = $userFactory->loadUserById($user_id);
        } else {
            $user   = $this->getCurrentUser();
            $html[] = '<input type="hidden" name="' . $this->name . '" value="' . $user->id . '" />';
        }

        if (!$this->hidden) {
            $html[] = "<div>" . $user->name . " (" . $user->username . ")</div>";
        }

        return implode('', $html);
    }
}
