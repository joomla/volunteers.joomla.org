<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  System.joomlaidentityclient
 *
 * @copyright   Copyright (C) 2005 - 2024 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Plugin\System\Joomlaidentityclient\Extension;

use Joomla\CMS\Event\Application\AfterRouteEvent;
use Joomla\CMS\Event\Model\PrepareFormEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla Identity Client Plugin class
 *
 * @since  1.0.0
 */
final class Joomlaidentityclient extends CMSPlugin implements SubscriberInterface
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
     * Returns an array of events this subscriber will listen to.
     *
     * @return  array
     *
     * @since   5.3.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterRoute' => 'onAfterRoute',
            'onContentPrepareForm' => 'onContentPrepareForm',
        ];
    }

    /**
     * Triggered before Joomla! renders the page
     *
     * @param   AfterRouteEvent  $event  The event object
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onAfterRoute(AfterRouteEvent $event): void
    {
        $app = $this->getApplication();

        // Run on frontend only
        if (!$app->isClient('site'))
        {
            return;
        }

        // Check for Joomla Identity
        $joomlaIdentity = $app->getInput()->getBool('joomlaidp');

        // Only continue for Joomla Identity
        if (!isset($joomlaIdentity))
        {
            return;
        }

        Log::addLogger(['text_file' => 'idp-calls.php'], Log::ALL, ['idp']);
        Log::add('Call received', Log::INFO, 'idp');

        try
        {
            // Get the API key
            $apiKey = $this->params->get('apikey');

            // Check API key length
            if (strlen($apiKey) <> 36)
            {
                Log::add(Text::_('PLG_SYSTEM_JOOMLAIDENTITYCLIENT_INVALID_APIKEY'), Log::INFO, 'idp');
                throw new \InvalidArgumentException(Text::_('PLG_SYSTEM_JOOMLAIDENTITYCLIENT_INVALID_APIKEY'));
            }

            // Get the payload, unfiltered as filtering breaks the HTML in the data and with that the hash
            $data = $app->getInput()->get('data', '', 'raw');
            $hash = base64_decode($app->getInput()->get('hash', '', 'base64'));

            // Validate the hash
            if (!hash_equals(hash_hmac('sha512', $data, $apiKey), (string) $hash))
            {
                Log::add(Text::_('PLG_SYSTEM_JOOMLAIDENTITYCLIENT_INVALID_HASH'), Log::INFO, 'idp');
                throw new \InvalidArgumentException(Text::_('PLG_SYSTEM_JOOMLAIDENTITYCLIENT_INVALID_HASH'));
            }

            Log::add('Data: ' . $data, Log::INFO, 'idp');

            // Get the data
            $data     = json_decode($data);
            $guid     = $data->guid;
            $task     = $data->task;
            $userData = $data->userData;

            if (!$guid || strlen($guid) <> 36)
            {
                Log::add(Text::_('PLG_SYSTEM_JOOMLAIDENTITYCLIENT_INVALID_GUID'), Log::INFO, 'idp');
                throw new \InvalidArgumentException(Text::_('PLG_SYSTEM_JOOMLAIDENTITYCLIENT_INVALID_GUID'));
            }

            if ($userData)
            {
                $this->processIdentity($guid, $task, $userData);
            }
        }
        catch (\Exception $exception)
        {
            echo $exception->getMessage();
        }

        // Since we decided to handle this request, we will close it as well
        $app->close();
    }

    /**
     * Method to process the Joomla Identity data
     *
     * @param   string  $guid  The User ID
     * @param   string  $task  The task
     * @param   object  $data  Object containing user data
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function processIdentity(string $guid, string $task, object $data): void
    {
        // Update the Joomla user
        $this->updateJoomlaUser($guid, $data->name, $data->email);

        // Get Joomla user ID
        $userId = (int) UserHelper::getUserId($guid);

        // Plugin trigger onProcessIdentity for site specific processing
        $this->getApplication()->getDispatcher()->dispatch(
            'onProcessIdentity',
            new Event(
                'onProcessIdentity',
                [
                    'userId' => $userId,
                    'guid'   => $guid,
                    'task'   => $task,
                    'data'   => $data,
                ]
            )
        );
    }

    /**
     * Method to update the Joomla User
     *
     * @param   string  $username  Username (guid)
     * @param   string  $name      Name of user
     * @param   string  $email     Email of user
     *
     * @return  void
     *
     * @since   1.0.0
     */
    protected function updateJoomlaUser(string $username, string $name, string $email): void
    {
        // Consent date
        $user = (object) array(
            'username' => $username,
            'name'     => $name,
            'email'    => $email,
        );

        $this->getDatabase()->updateObject('#__users', $user, 'username');
    }

    /**
     * We prevent editing the user properties via the site itself
     *
     * @param   PrepareFormEvent  $event  The event object
     *
     * @return  void
     *
     * @since   1.0.0
     */
    public function onContentPrepareForm(PrepareFormEvent $event): void
    {
        $form = $event->getForm();
        $data = $event->getData();

        // Check we are manipulating a valid form.
        if (!\in_array($form->getName(), array('com_admin.profile', 'com_users.user', 'com_users.profile', 'com_users.registration')))
        {
            return;
        }

        // Disable editing the name, username, email, password and requireReset fields
        $form->setFieldAttribute('name', 'readonly', 'readonly');
        $form->setFieldAttribute('username', 'readonly', 'readonly');
        $form->setFieldAttribute('email', 'readonly', 'readonly');
        $form->setFieldAttribute('password', 'readonly', 'readonly');
        $form->setFieldAttribute('password2', 'readonly', 'readonly');
        $form->setFieldAttribute('requireReset', 'readonly', 'readonly');
    }
}