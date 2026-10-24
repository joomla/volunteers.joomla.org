<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.joomlaidentityvolunteers
 *
 * @copyright   Copyright (C) 2005 - 2024 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\Joomlaidentityvolunteers\Extension;

use Exception;
use InvalidArgumentException;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use stdClass;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla Identity Volunteers Plugin class
 *
 * @since  1.0.0
 */
final class Joomlaidentityvolunteers extends CMSPlugin implements SubscriberInterface
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
    private array $requiredFields = [
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
        'crowdin'
    ];

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   5.3.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onProcessIdentity' => 'onProcessIdentity',
        ];
    }

    /**
     * Method triggered in processing Joomla identity data
     *
     * The Joomla Identity Client plugin passes the arguments userId, guid, task and data.
     *
     * @param   Event  $event  The event object
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onProcessIdentity(Event $event): void
    {
        $userId = (int) $event->getArgument('userId');
        $guid   = (string) $event->getArgument('guid');
        $data   = $event->getArgument('data');

        try
        {
            $this->validateData($data);
            $this->updateVolunteer($userId, $guid, $data);
        }
        catch (\Exception $exception)
        {
            echo $exception->getMessage();
        }
    }

    /**
     * Validate the posted data containing al the required fields.
     *
     * @param   stdClass  $data  The data to store
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function validateData(stdClass $data): void
    {
        foreach ($this->requiredFields as $field)
        {
            if (!isset($data->$field))
            {
                throw new InvalidArgumentException(Text::sprintf('PLG_SYSTEM_JOOMLAIDENTITYVOLUNTEERS_MISSING_FIELD', $field));
            }
        }
    }

    /**
     * Method to update the volunteer data
     *
     * @param   integer $userId Joomla User ID
     * @param   string  $guid   GUID of user
     * @param   object  $data   Object containing user data
     *
     * @return  void
     *
     * @since   1.0.0
     */
    private function updateVolunteer(int $userId, string $guid, object $data): void
    {
        // Consent date
        $volunteer = (object) array(
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
        );

        Log::add(json_encode($volunteer), Log::INFO, 'idp');

        try
        {
            $this->getDatabase()->insertObject('#__volunteers_volunteers', $volunteer, 'user_id');
        }
        catch (Exception $e)
        {
            $this->getDatabase()->updateObject('#__volunteers_volunteers', $volunteer, ['user_id']);
        }
    }
}
