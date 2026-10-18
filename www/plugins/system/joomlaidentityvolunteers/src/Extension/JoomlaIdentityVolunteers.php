<?php

/**
 * @package         Joomla.Plugins
 * @subpackage      System.actionlogs
 *
 * @copyright   (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\JoomlaIdentityVolunteers\Extension;

use Joomla\Event\SubscriberInterface;
use Exception;
use InvalidArgumentException;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla Identity Plugin class
 *
 * @since  4.0.0
 */
final class JoomlaIdentityVolunteers extends CMSPlugin implements SubscriberInterface
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
     * The required volunteer data
     *
     * @var    array
     * @since  1.0.0
     */
    private $requiredFields = [
        'address',
        'city',
        'region',
        'zip',
        'country',
        'intro',
        'joomlastory',
        'image',
        'facebook',
        'twitter',
        'linkedin',
        'website',
        'github',
        'certification',
        'stackexchange',
        'joomlastackexchange',
        'latitude',
        'longitude',
        'joomlaforum',
        'joomladocs',
        'crowdin',
    ];

    /**
     * Method triggered in processing Joomla identity data
     *
     * @param   integer  $userId  Joomla User ID
     * @param   string   $guid    GUID of user
     * @param   string   $task    Task triggered
     * @param   object   $data    Object containing user data
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function onProcessIdentity(int $userId, string $guid, string $task, object $data)
    {
        try {
            $this->validateData($data);
            $this->updateVolunteer($userId, $guid, $data);
        } catch (Exception $exception) {
            echo $exception->getMessage();
        }
    }

    /**
     * Validate the posted data containing all the required fields.
     *
     * @param   object  $data  The data to store
     *
     * @return  void
     *
     * @since   4.0.0
     * @throws  InvalidArgumentException
     *
     */
    private function validateData(object $data)
    {
        foreach ($this->requiredFields as $field) {
            if (!isset($data->$field)) {
                throw new InvalidArgumentException(Text::sprintf('PLG_SYSTEM_JOOMLAIDENTITYVOLUNTEERS_MISSING_FIELD', $field));
            }
        }
    }

    /**
     * Method to update the volunteer data
     *
     * @param   integer  $userId  Joomla User ID
     * @param   string   $guid    GUID of user
     * @param   object   $data    Object containing user data
     *
     * @return  void
     *
     * @since   4.0.0
     */
    private function updateVolunteer(int $userId, string $guid, object $data)
    {
        // Consent date
        $volunteer = (object)[
            'user_id'             => $userId,
            'alias'               => ApplicationHelper::stringURLSafe($data->name),
            'address'             => $data->address,
            'city'                => $data->city,
            'city-location'       => $data->city_location,
            'region'              => $data->region,
            'zip'                 => $data->zip,
            'country'             => $data->country,
            'intro'               => $data->intro,
            'joomlastory'         => $data->joomlastory,
            'image'               => ($data->image) ? 'https://identity.joomla.org/' . $data->image : '',
            'facebook'            => $data->facebook,
            'twitter'             => $data->twitter,
            'linkedin'            => $data->linkedin,
            'website'             => $data->website,
            'github'              => $data->github,
            'certification'       => $data->certification,
            'stackexchange'       => $data->stackexchange,
            'joomlastackexchange' => $data->joomlastackexchange,
            'latitude'            => $data->latitude,
            'longitude'           => $data->longitude,
            'joomlaforum'         => $data->joomlaforum,
            'joomladocs'          => $data->joomladocs,
            'crowdin'             => $data->crowdin,
            'osmAddress'          => $data->osmAddress,
            'nda'                 => $data->nda,
        ];

        Log::add(json_encode($volunteer), Log::INFO, 'idpjvp');

        try {
            $this->getDatabase()->insertObject('#__volunteers_volunteers', $volunteer, 'user_id');
        } catch (Exception) {
            $this->getDatabase()->updateObject('#__volunteers_volunteers', $volunteer, ['user_id']);
        }
    }
    public static function getSubscribedEvents(): array
    {
        return ['onProcessIdentity' => 'onProcessIdentity'];
    }
}
